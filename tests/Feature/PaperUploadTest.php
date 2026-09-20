<?php

namespace Tests\Feature;

use App\Enums\PaperStatus;
use App\Enums\RoleName;
use App\Models\AuditLog;
use App\Models\Paper;
use App\Models\ReviewerAssignment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use Tests\TestCase;

class PaperUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Storage::fake('paper-files');
        config()->set('papers.storage_disk', 'paper-files');
    }

    public function test_private_paper_disk_is_isolated_from_web_served_disks(): void
    {
        $paperRoot = config('filesystems.disks.paper-files.root');

        $this->assertSame(storage_path('app/paper-files'), $paperRoot);
        $this->assertFalse((bool) config('filesystems.disks.paper-files.serve'));
        $this->assertNotSame(config('filesystems.disks.local.root'), $paperRoot);
        $this->assertNotSame(config('filesystems.disks.public.root'), $paperRoot);
    }

    public function test_researcher_can_securely_upload_a_paper_with_normalized_metadata(): void
    {
        $researcher = $this->userWithRole(RoleName::Researcher);
        $pdf = $this->pdf('author supplied title.pdf');
        $contents = $pdf->getContent();

        $response = $this
            ->actingAs($researcher)
            ->withHeader('User-Agent', 'PaperUploadTest/1.0')
            ->post('/papers', [
                'title' => '  Explainable AI for Clinical Research  ',
                'abstract' => '  A reproducible study.  ',
                'publication_year' => '2025',
                'journal' => '  Journal of Trustworthy AI  ',
                'doi' => '  10.1000/ABC-123  ',
                'keywords' => ' Machine Learning, explainability, machine learning ',
                'authors' => [
                    [
                        'name' => '  Ada Lovelace  ',
                        'email' => ' ADA@example.test ',
                        'affiliation' => '  Analytical Engine Institute  ',
                        'orcid' => '0000-0002-1825-0097',
                    ],
                    ['name' => 'Grace Hopper'],
                ],
                'file' => $pdf,
            ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('success', 'Paper uploaded securely.');

        $paper = Paper::query()->with('authors')->sole();

        $response->assertRedirect("/papers/{$paper->id}");

        $this->assertSame('Explainable AI for Clinical Research', $paper->title);
        $this->assertSame('A reproducible study.', $paper->abstract);
        $this->assertSame(2025, $paper->publication_year);
        $this->assertSame('Journal of Trustworthy AI', $paper->journal);
        $this->assertSame('10.1000/abc-123', $paper->doi);
        $this->assertSame(['Machine Learning', 'explainability'], $paper->keywords);
        $this->assertSame(PaperStatus::Uploaded, $paper->status);
        $this->assertSame($researcher->id, $paper->uploaded_by);

        $this->assertSame('paper-files', $paper->storage_disk);
        $this->assertSame('author supplied title.pdf', $paper->original_filename);
        $this->assertSame('application/pdf', $paper->mime_type);
        $this->assertSame(strlen($contents), $paper->file_size);
        $this->assertSame(hash('sha256', $contents), $paper->checksum_sha256);
        $this->assertStringStartsWith("papers/{$researcher->id}/", $paper->file_path);
        $this->assertStringEndsWith('.pdf', $paper->file_path);
        $this->assertStringNotContainsString('author supplied title', $paper->file_path);
        Storage::disk('paper-files')->assertExists($paper->file_path);
        $this->assertSame($contents, Storage::disk('paper-files')->get($paper->file_path));

        $this->assertSame(
            [
                ['name' => 'Ada Lovelace', 'email' => 'ADA@example.test', 'affiliation' => 'Analytical Engine Institute', 'orcid' => '0000-0002-1825-0097', 'author_order' => 1],
                ['name' => 'Grace Hopper', 'email' => null, 'affiliation' => null, 'orcid' => null, 'author_order' => 2],
            ],
            $paper->authors
                ->map(fn ($author): array => $author->only(['name', 'email', 'affiliation', 'orcid', 'author_order']))
                ->all(),
        );

        $audit = AuditLog::query()->sole();
        $this->assertSame($researcher->id, $audit->actor_id);
        $this->assertSame('paper.created', $audit->action);
        $this->assertSame(Paper::class, $audit->target_type);
        $this->assertSame($paper->id, $audit->target_id);
        $this->assertSame('PaperUploadTest/1.0', $audit->user_agent);
        $this->assertDatabaseCount('ai_jobs', 0);
    }

    public function test_researcher_can_open_the_upload_form_with_the_server_upload_limit(): void
    {
        $researcher = $this->userWithRole(RoleName::Researcher);

        $this->actingAs($researcher)
            ->get('/papers/create')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Papers/Create')
                ->where('maxUploadMegabytes', 20)
                ->where('maxPublicationYear', now()->year + 1)
            );
    }

    public function test_upload_rejects_a_non_pdf_even_when_the_filename_ends_in_pdf(): void
    {
        $researcher = $this->userWithRole(RoleName::Researcher);
        $path = tempnam(sys_get_temp_dir(), 'paper-spoof-');
        $this->assertIsString($path);
        file_put_contents($path, 'plain text, not a PDF');

        try {
            $spoofedPdf = new UploadedFile($path, 'spoofed.pdf', null, UPLOAD_ERR_OK, true);

            $this->actingAs($researcher)
                ->from('/papers/create')
                ->post('/papers', [
                    'title' => 'Spoofed upload',
                    'file' => $spoofedPdf,
                ])
                ->assertRedirect('/papers/create')
                ->assertSessionHasErrors('file');
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }

        $this->assertDatabaseCount('papers', 0);
        $this->assertDatabaseCount('audit_logs', 0);
        $this->assertSame([], Storage::disk('paper-files')->allFiles());
    }

    public function test_upload_rejects_pdf_content_when_the_client_extension_is_not_pdf(): void
    {
        $researcher = $this->userWithRole(RoleName::Researcher);
        $path = tempnam(sys_get_temp_dir(), 'paper-extension-');
        $this->assertIsString($path);
        file_put_contents($path, "%PDF-1.7\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n");

        try {
            $wrongExtension = new UploadedFile($path, 'paper.txt', null, UPLOAD_ERR_OK, true);

            $this->actingAs($researcher)
                ->from('/papers/create')
                ->post('/papers', [
                    'title' => 'Wrong extension',
                    'file' => $wrongExtension,
                ])
                ->assertRedirect('/papers/create')
                ->assertSessionHasErrors('file');
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }

        $this->assertDatabaseCount('papers', 0);
        $this->assertDatabaseCount('audit_logs', 0);
        $this->assertSame([], Storage::disk('paper-files')->allFiles());
    }

    public function test_upload_rejects_a_pdf_larger_than_the_configured_limit(): void
    {
        $researcher = $this->userWithRole(RoleName::Researcher);
        config()->set('papers.max_upload_kilobytes', 1);
        $oversizedPdf = UploadedFile::fake()->create('oversized.pdf', 2, 'application/pdf');

        $this->actingAs($researcher)
            ->from('/papers/create')
            ->post('/papers', [
                'title' => 'Oversized upload',
                'file' => $oversizedPdf,
            ])
            ->assertRedirect('/papers/create')
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('papers', 0);
        $this->assertDatabaseCount('audit_logs', 0);
        $this->assertSame([], Storage::disk('paper-files')->allFiles());
    }

    public function test_upload_rejects_malformed_metadata_collections(): void
    {
        $researcher = $this->userWithRole(RoleName::Researcher);

        $this->actingAs($researcher)
            ->from('/papers/create')
            ->post('/papers', [
                'title' => 'Malformed metadata',
                'keywords' => 123,
                'authors' => 'not-an-array',
                'file' => $this->pdf('malformed.pdf'),
            ])
            ->assertRedirect('/papers/create')
            ->assertSessionHasErrors(['keywords', 'authors']);

        $this->assertDatabaseCount('papers', 0);
        $this->assertDatabaseCount('audit_logs', 0);
        $this->assertSame([], Storage::disk('paper-files')->allFiles());
    }

    public function test_upload_rejects_non_list_metadata_collections(): void
    {
        $researcher = $this->userWithRole(RoleName::Researcher);

        $this->actingAs($researcher)
            ->from('/papers/create')
            ->post('/papers', [
                'title' => 'Associative metadata',
                'keywords' => ['topic' => 'security'],
                'authors' => ['lead' => ['name' => 'Ada Lovelace']],
                'file' => $this->pdf('associative.pdf'),
            ])
            ->assertRedirect('/papers/create')
            ->assertSessionHasErrors(['keywords', 'authors']);

        $this->assertDatabaseCount('papers', 0);
        $this->assertDatabaseCount('audit_logs', 0);
        $this->assertSame([], Storage::disk('paper-files')->allFiles());
    }

    public function test_upload_rejects_unknown_author_fields_without_side_effects(): void
    {
        $researcher = $this->userWithRole(RoleName::Researcher);

        $this->actingAs($researcher)
            ->from('/papers/create')
            ->post('/papers', [
                'title' => 'Unexpected author field',
                'authors' => [[
                    'name' => 'Ada Lovelace',
                    'uploaded_by' => $researcher->id,
                ]],
                'file' => $this->pdf('unexpected-author-field.pdf'),
            ])
            ->assertRedirect('/papers/create')
            ->assertSessionHasErrors('authors.0');

        $this->assertDatabaseCount('papers', 0);
        $this->assertDatabaseCount('audit_logs', 0);
        $this->assertSame([], Storage::disk('paper-files')->allFiles());
    }

    public function test_upload_removes_a_partial_private_pdf_when_storage_reports_failure(): void
    {
        $researcher = $this->userWithRole(RoleName::Researcher);
        $originalDisk = Storage::disk('paper-files');
        $failingDisk = Mockery::mock(FilesystemAdapter::class);
        $failingDisk->shouldReceive('putFileAs')
            ->once()
            ->andReturnUsing(function (string $directory, UploadedFile $file, string $name) use ($originalDisk): bool {
                $originalDisk->put("{$directory}/{$name}", 'partial PDF');

                return false;
            });
        $failingDisk->shouldReceive('delete')
            ->zeroOrMoreTimes()
            ->andReturnUsing(static fn (string $path): bool => $originalDisk->delete($path));
        Storage::getFacadeRoot()->set('paper-files', $failingDisk);

        try {
            $this->withoutExceptionHandling();
            $this->actingAs($researcher)->post('/papers', [
                'title' => 'Partial storage failure',
                'file' => $this->pdf('partial.pdf'),
            ]);
            $this->fail('The storage failure was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Unable to store the uploaded PDF.', $exception->getMessage());
        } finally {
            Storage::getFacadeRoot()->set('paper-files', $originalDisk);
        }

        $this->assertDatabaseCount('papers', 0);
        $this->assertDatabaseCount('audit_logs', 0);
        $this->assertSame([], $originalDisk->allFiles());
    }

    public function test_upload_keeps_the_private_pdf_when_an_exception_occurs_after_database_commit(): void
    {
        $researcher = $this->userWithRole(RoleName::Researcher);
        $events = DB::connection()->getEventDispatcher();
        $events->listen(TransactionCommitted::class, static function (): never {
            throw new \RuntimeException('Forced failure after database commit.');
        });

        try {
            $this->withoutExceptionHandling();
            $this->actingAs($researcher)->post('/papers', [
                'title' => 'Committed upload',
                'file' => $this->pdf('committed.pdf'),
            ]);
            $this->fail('The post-commit failure was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Forced failure after database commit.', $exception->getMessage());
        } finally {
            $events->forget(TransactionCommitted::class);
        }

        $paper = Paper::query()->where('title', 'Committed upload')->sole();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'paper.created',
            'target_id' => $paper->id,
        ]);
        Storage::disk('paper-files')->assertExists($paper->file_path);
    }

    public function test_upload_reports_cleanup_failure_without_hiding_the_persistence_failure(): void
    {
        $researcher = $this->userWithRole(RoleName::Researcher);
        $originalDisk = Storage::disk('paper-files');
        $failingDisk = Mockery::mock(FilesystemAdapter::class);
        $failingDisk->shouldReceive('putFileAs')
            ->once()
            ->andReturnUsing(function (string $directory, UploadedFile $file, string $name) use ($originalDisk): string {
                $path = "{$directory}/{$name}";
                $originalDisk->put($path, 'private PDF');

                return $path;
            });
        $failingDisk->shouldReceive('delete')
            ->once()
            ->andReturnFalse();
        Storage::getFacadeRoot()->set('paper-files', $failingDisk);
        $originalDispatcher = Paper::getEventDispatcher();
        Paper::setEventDispatcher(new Dispatcher($this->app));
        Paper::creating(static function (): never {
            throw new \RuntimeException('Forced paper persistence failure.');
        });

        try {
            $this->withoutExceptionHandling();
            $this->actingAs($researcher)->post('/papers', [
                'title' => 'Cleanup failure',
                'file' => $this->pdf('cleanup-failure.pdf'),
            ]);
            $this->fail('The cleanup failure was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Unable to clean up the private PDF after upload failure.', $exception->getMessage());
            $this->assertSame('Forced paper persistence failure.', $exception->getPrevious()?->getMessage());
        } finally {
            Paper::setEventDispatcher($originalDispatcher);
            Storage::getFacadeRoot()->set('paper-files', $originalDisk);
        }

        $this->assertDatabaseCount('papers', 0);
        $this->assertDatabaseCount('audit_logs', 0);
        $this->assertCount(1, $originalDisk->allFiles());
    }

    public function test_upload_removes_the_private_pdf_when_database_persistence_fails(): void
    {
        $researcher = $this->userWithRole(RoleName::Researcher);
        $originalDispatcher = Paper::getEventDispatcher();
        Paper::setEventDispatcher(new Dispatcher($this->app));
        Paper::creating(static function (): never {
            throw new \RuntimeException('Forced paper persistence failure.');
        });

        try {
            $this->withoutExceptionHandling();
            $this->actingAs($researcher)->post('/papers', [
                'title' => 'Database failure cleanup',
                'file' => $this->pdf('cleanup.pdf'),
            ]);
            $this->fail('The forced persistence failure was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Forced paper persistence failure.', $exception->getMessage());
        } finally {
            Paper::setEventDispatcher($originalDispatcher);
        }

        $this->assertDatabaseCount('papers', 0);
        $this->assertDatabaseCount('paper_authors', 0);
        $this->assertDatabaseCount('audit_logs', 0);
        $this->assertSame([], Storage::disk('paper-files')->allFiles());
    }

    public function test_owner_can_view_allowlisted_paper_metadata_without_private_storage_details(): void
    {
        $researcher = $this->userWithRole(RoleName::Researcher);
        $paper = Paper::query()->create([
            'title' => 'Private storage paper',
            'abstract' => 'Visible abstract',
            'publication_year' => 2024,
            'journal' => 'Visible Journal',
            'doi' => '10.1000/visible',
            'keywords' => ['security'],
            'file_path' => 'papers/secret/server-name.pdf',
            'storage_disk' => 'paper-files',
            'original_filename' => 'private-original.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1234,
            'checksum_sha256' => str_repeat('a', 64),
            'status' => PaperStatus::Uploaded,
            'uploaded_by' => $researcher->id,
        ]);
        $paper->authors()->create([
            'name' => 'Ada Lovelace',
            'email' => 'private@example.test',
            'affiliation' => 'Analytical Engine Institute',
            'orcid' => '0000-0002-1825-0097',
            'author_order' => 1,
        ]);

        $this->actingAs($researcher)
            ->get("/papers/{$paper->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Papers/Show')
                ->has('paper', fn (Assert $paperProp) => $paperProp
                    ->where('id', $paper->id)
                    ->where('title', 'Private storage paper')
                    ->where('abstract', 'Visible abstract')
                    ->where('publication_year', 2024)
                    ->where('journal', 'Visible Journal')
                    ->where('doi', '10.1000/visible')
                    ->where('keywords', ['security'])
                    ->where('status', PaperStatus::Uploaded->value)
                    ->has('authors', 1, fn (Assert $author) => $author
                        ->where('name', 'Ada Lovelace')
                        ->where('affiliation', 'Analytical Engine Institute')
                        ->where('orcid', '0000-0002-1825-0097')
                        ->where('author_order', 1)
                        ->missing('email')
                        ->etc()
                    )
                    ->missing('file_path')
                    ->missing('storage_disk')
                    ->missing('original_filename')
                    ->missing('mime_type')
                    ->missing('file_size')
                    ->missing('checksum_sha256')
                    ->etc()
                )
                ->where('can.download', true)
                ->where('can.update', true)
                ->where('can.delete', true)
            );
    }

    public function test_owner_can_download_the_private_pdf(): void
    {
        $researcher = $this->userWithRole(RoleName::Researcher);
        $paper = $this->paperUploadedBy($researcher, 'Downloadable paper');
        $contents = "%PDF-1.7\nprivate paper\n%%EOF\n";
        Storage::disk('paper-files')->put($paper->file_path, $contents);

        $response = $this
            ->actingAs($researcher)
            ->get("/papers/{$paper->id}/download");

        $response
            ->assertOk()
            ->assertDownload("paper-{$paper->id}.pdf");
        $this->assertSame($contents, $response->streamedContent());
    }

    public function test_download_never_trusts_the_stored_original_filename(): void
    {
        $researcher = $this->userWithRole(RoleName::Researcher);
        $paper = $this->paperUploadedBy($researcher, 'Download name test');
        $paper->update(['original_filename' => "../../report\r\nX-Evil: injected.pdf"]);
        Storage::disk('paper-files')->put($paper->file_path, 'private PDF');

        $response = $this
            ->actingAs($researcher)
            ->get("/papers/{$paper->id}/download");

        $response
            ->assertOk()
            ->assertDownload("paper-{$paper->id}.pdf")
            ->assertHeaderMissing('X-Evil');
    }

    public function test_download_returns_not_found_when_the_private_pdf_is_missing(): void
    {
        $researcher = $this->userWithRole(RoleName::Researcher);
        $paper = $this->paperUploadedBy($researcher, 'Missing private PDF');

        Storage::disk('paper-files')->assertMissing($paper->file_path);

        $this->actingAs($researcher)
            ->get("/papers/{$paper->id}/download")
            ->assertNotFound();
    }

    public function test_download_fails_closed_when_storage_metadata_is_not_the_private_paper_disk(): void
    {
        $researcher = $this->userWithRole(RoleName::Researcher);
        $paper = $this->paperUploadedBy($researcher, 'Unsafe storage metadata');
        $paper->update(['storage_disk' => 'public']);
        Storage::fake('public');
        Storage::disk('public')->put($paper->file_path, 'public PDF');

        $this->actingAs($researcher)
            ->get("/papers/{$paper->id}/download")
            ->assertConflict();

        Storage::disk('public')->assertExists($paper->file_path);
    }

    public function test_owner_can_update_metadata_and_authors_without_changing_private_storage_fields(): void
    {
        $researcher = $this->userWithRole(RoleName::Researcher);
        $attacker = $this->userWithRole(RoleName::Researcher);
        $paper = $this->paperUploadedBy($researcher, 'Original title');
        $paper->authors()->create(['name' => 'Original Author', 'author_order' => 1]);
        Storage::disk('paper-files')->put($paper->file_path, 'original file');
        $protected = $paper->only([
            'uploaded_by',
            'file_path',
            'storage_disk',
            'original_filename',
            'mime_type',
            'file_size',
            'checksum_sha256',
            'status',
        ]);

        $response = $this->actingAs($researcher)->patch("/papers/{$paper->id}", [
            'title' => '  Updated title  ',
            'abstract' => '  Updated abstract  ',
            'publication_year' => '2026',
            'journal' => '  Updated Journal  ',
            'doi' => '  10.1000/UPDATED  ',
            'keywords' => [' Research ', 'research', 'Safety'],
            'authors' => [
                ['name' => '  Grace Hopper  ', 'affiliation' => '  Navy  '],
                ['name' => 'Alan Turing', 'orcid' => '0000-0002-1825-0097'],
            ],
            'uploaded_by' => $attacker->id,
            'file_path' => 'public/injected.pdf',
            'storage_disk' => 'public',
            'original_filename' => 'injected.pdf',
            'mime_type' => 'text/plain',
            'file_size' => 1,
            'checksum_sha256' => str_repeat('0', 64),
            'status' => PaperStatus::Failed->value,
        ]);

        $response
            ->assertRedirect("/papers/{$paper->id}")
            ->assertSessionHas('success', 'Paper metadata updated.');

        $paper->refresh()->load('authors');
        $this->assertSame('Updated title', $paper->title);
        $this->assertSame('Updated abstract', $paper->abstract);
        $this->assertSame(2026, $paper->publication_year);
        $this->assertSame('Updated Journal', $paper->journal);
        $this->assertSame('10.1000/updated', $paper->doi);
        $this->assertSame(['Research', 'Safety'], $paper->keywords);
        $this->assertSame($protected, $paper->only(array_keys($protected)));
        Storage::disk('paper-files')->assertExists($paper->file_path);
        $this->assertSame('original file', Storage::disk('paper-files')->get($paper->file_path));
        $this->assertSame(
            [
                ['name' => 'Grace Hopper', 'affiliation' => 'Navy', 'orcid' => null, 'author_order' => 1],
                ['name' => 'Alan Turing', 'affiliation' => null, 'orcid' => '0000-0002-1825-0097', 'author_order' => 2],
            ],
            $paper->authors
                ->map(fn ($author): array => $author->only(['name', 'affiliation', 'orcid', 'author_order']))
                ->all(),
        );

        $audit = AuditLog::query()->sole();
        $this->assertSame('paper.updated', $audit->action);
        $this->assertSame($researcher->id, $audit->actor_id);
        $this->assertSame($paper->id, $audit->target_id);
    }

    public function test_update_rejects_malformed_metadata_collections_without_side_effects(): void
    {
        $researcher = $this->userWithRole(RoleName::Researcher);
        $paper = $this->paperUploadedBy($researcher, 'Original title');
        $author = $paper->authors()->create(['name' => 'Original Author', 'author_order' => 1]);

        $this->actingAs($researcher)
            ->from("/papers/{$paper->id}/edit")
            ->patch("/papers/{$paper->id}", [
                'title' => 'Changed title',
                'keywords' => 123,
                'authors' => 'not-an-array',
            ])
            ->assertRedirect("/papers/{$paper->id}/edit")
            ->assertSessionHasErrors(['keywords', 'authors']);

        $this->assertSame('Original title', $paper->fresh()?->title);
        $this->assertDatabaseHas('paper_authors', [
            'id' => $author->id,
            'name' => 'Original Author',
        ]);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_update_rejects_non_list_metadata_collections_without_side_effects(): void
    {
        $researcher = $this->userWithRole(RoleName::Researcher);
        $paper = $this->paperUploadedBy($researcher, 'Original title');
        $author = $paper->authors()->create(['name' => 'Original Author', 'author_order' => 1]);

        $this->actingAs($researcher)
            ->from("/papers/{$paper->id}/edit")
            ->patch("/papers/{$paper->id}", [
                'title' => 'Changed title',
                'keywords' => ['topic' => 'security'],
                'authors' => ['lead' => ['name' => 'Ada Lovelace']],
            ])
            ->assertRedirect("/papers/{$paper->id}/edit")
            ->assertSessionHasErrors(['keywords', 'authors']);

        $this->assertSame('Original title', $paper->fresh()?->title);
        $this->assertDatabaseHas('paper_authors', [
            'id' => $author->id,
            'name' => 'Original Author',
        ]);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_update_rejects_unknown_author_fields_without_side_effects(): void
    {
        $researcher = $this->userWithRole(RoleName::Researcher);
        $paper = $this->paperUploadedBy($researcher, 'Original title');
        $author = $paper->authors()->create(['name' => 'Original Author', 'author_order' => 1]);

        $this->actingAs($researcher)
            ->from("/papers/{$paper->id}/edit")
            ->patch("/papers/{$paper->id}", [
                'title' => 'Changed title',
                'authors' => [[
                    'name' => 'Ada Lovelace',
                    'paper_id' => 999,
                ]],
            ])
            ->assertRedirect("/papers/{$paper->id}/edit")
            ->assertSessionHasErrors('authors.0');

        $this->assertSame('Original title', $paper->fresh()?->title);
        $this->assertDatabaseHas('paper_authors', [
            'id' => $author->id,
            'name' => 'Original Author',
        ]);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_owner_can_open_an_edit_form_without_private_storage_metadata(): void
    {
        $researcher = $this->userWithRole(RoleName::Researcher);
        $paper = $this->paperUploadedBy($researcher, 'Editable paper');
        $paper->authors()->create([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.test',
            'affiliation' => 'Analytical Engine Institute',
            'orcid' => '0000-0002-1825-0097',
            'author_order' => 1,
        ]);

        $this->actingAs($researcher)
            ->get("/papers/{$paper->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Papers/Edit')
                ->where('maxPublicationYear', now()->year + 1)
                ->has('paper', fn (Assert $paperProp) => $paperProp
                    ->where('id', $paper->id)
                    ->where('title', 'Editable paper')
                    ->where('authors.0.name', 'Ada Lovelace')
                    ->where('authors.0.email', 'ada@example.test')
                    ->missing('file_path')
                    ->missing('storage_disk')
                    ->missing('original_filename')
                    ->missing('checksum_sha256')
                    ->etc()
                )
            );
    }

    public function test_owner_can_delete_a_paper_and_its_private_file_while_retaining_an_audit_record(): void
    {
        $researcher = $this->userWithRole(RoleName::Researcher);
        $paper = $this->paperUploadedBy($researcher, 'Paper to delete');
        $paper->authors()->create(['name' => 'Ada Lovelace', 'author_order' => 1]);
        Storage::disk('paper-files')->put($paper->file_path, 'private PDF');
        $paperId = $paper->id;
        $filePath = $paper->file_path;

        $response = $this
            ->actingAs($researcher)
            ->delete("/papers/{$paperId}");

        $response
            ->assertRedirect('/papers')
            ->assertSessionHas('success', 'Paper deleted.');
        $this->assertDatabaseMissing('papers', ['id' => $paperId]);
        $this->assertDatabaseMissing('paper_authors', ['paper_id' => $paperId]);
        Storage::disk('paper-files')->assertMissing($filePath);

        $audit = AuditLog::query()->sole();
        $this->assertSame('paper.deleted', $audit->action);
        $this->assertSame($researcher->id, $audit->actor_id);
        $this->assertSame(Paper::class, $audit->target_type);
        $this->assertSame($paperId, $audit->target_id);
        $this->assertSame(['title' => 'Paper to delete'], $audit->metadata);
    }

    public function test_delete_fails_closed_when_storage_metadata_is_unknown(): void
    {
        $researcher = $this->userWithRole(RoleName::Researcher);
        $paper = $this->paperUploadedBy($researcher, 'Legacy paper');
        $paper->update(['storage_disk' => null]);

        $this->actingAs($researcher)
            ->delete("/papers/{$paper->id}")
            ->assertConflict();

        $this->assertDatabaseHas('papers', ['id' => $paper->id]);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_delete_rolls_back_when_the_private_pdf_cannot_be_deleted(): void
    {
        $researcher = $this->userWithRole(RoleName::Researcher);
        $paper = $this->paperUploadedBy($researcher, 'Paper with undeletable PDF');
        $paper->authors()->create(['name' => 'Ada Lovelace', 'author_order' => 1]);
        $originalDisk = Storage::disk('paper-files');
        $originalDisk->put($paper->file_path, 'private PDF');
        $source = fopen('php://temp', 'w+b');
        fwrite($source, 'private PDF');
        rewind($source);
        $failingDisk = Mockery::mock(FilesystemAdapter::class);
        $failingDisk->shouldReceive('exists')
            ->twice()
            ->with($paper->file_path)
            ->andReturnTrue();
        $failingDisk->shouldReceive('readStream')
            ->once()
            ->with($paper->file_path)
            ->andReturn($source);
        $failingDisk->shouldReceive('delete')
            ->once()
            ->with($paper->file_path)
            ->andReturnFalse();
        Storage::getFacadeRoot()->set('paper-files', $failingDisk);

        try {
            $this->withoutExceptionHandling();
            $this->actingAs($researcher)->delete("/papers/{$paper->id}");
            $this->fail('The storage deletion failure was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Unable to delete the private PDF.', $exception->getMessage());
        } finally {
            Storage::getFacadeRoot()->set('paper-files', $originalDisk);
        }

        $this->assertDatabaseHas('papers', ['id' => $paper->id]);
        $this->assertDatabaseHas('paper_authors', ['paper_id' => $paper->id]);
        $this->assertDatabaseCount('audit_logs', 0);
        $originalDisk->assertExists($paper->file_path);
    }

    public function test_researcher_library_lists_only_owned_papers_with_allowlisted_metadata(): void
    {
        $researcher = $this->userWithRole(RoleName::Researcher);
        $otherResearcher = $this->userWithRole(RoleName::Researcher);
        $olderPaper = $this->paperUploadedBy($researcher, 'Older owned paper');
        $newerPaper = $this->paperUploadedBy($researcher, 'Newer owned paper');
        $this->paperUploadedBy($otherResearcher, 'Another researcher paper');
        $olderPaper->authors()->create(['name' => 'Older Author', 'author_order' => 1]);
        $newerPaper->authors()->create(['name' => 'Newer Author', 'author_order' => 1]);
        $olderPaper->forceFill(['created_at' => now()->subDay()])->save();

        $this->actingAs($researcher)
            ->get('/papers')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Papers/Index')
                ->has('papers.data', 2)
                ->where('papers.data.0.id', $newerPaper->id)
                ->where('papers.data.0.title', 'Newer owned paper')
                ->where('papers.data.0.authors.0.name', 'Newer Author')
                ->where('papers.data.1.id', $olderPaper->id)
                ->missing('papers.data.0.file_path')
                ->missing('papers.data.0.storage_disk')
                ->missing('papers.data.0.original_filename')
                ->missing('papers.data.0.checksum_sha256')
                ->where('filters.search', null)
                ->where('filters.status', null)
                ->where('can.create', true)
                ->where('statusOptions', [
                    ['value' => 'uploaded', 'label' => 'Uploaded'],
                    ['value' => 'processing', 'label' => 'Processing'],
                    ['value' => 'analyzed', 'label' => 'Analyzed'],
                    ['value' => 'failed', 'label' => 'Failed'],
                    ['value' => 'archived', 'label' => 'Archived'],
                ])
            );
    }

    public function test_admin_library_lists_all_papers(): void
    {
        $admin = $this->userWithRole(RoleName::Admin);
        $firstResearcher = $this->userWithRole(RoleName::Researcher);
        $secondResearcher = $this->userWithRole(RoleName::Researcher);
        $this->paperUploadedBy($firstResearcher, 'First researcher paper');
        $this->paperUploadedBy($secondResearcher, 'Second researcher paper');

        $this->actingAs($admin)
            ->get('/papers')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('papers.data', 2)
                ->where('papers.total', 2)
            );
    }

    public function test_reviewer_library_lists_only_assigned_papers(): void
    {
        $reviewer = $this->userWithRole(RoleName::Reviewer);
        $admin = $this->userWithRole(RoleName::Admin);
        $researcher = $this->userWithRole(RoleName::Researcher);
        $assigned = $this->paperUploadedBy($researcher, 'Assigned paper');
        $this->paperUploadedBy($researcher, 'Unassigned paper');
        ReviewerAssignment::query()->create([
            'paper_id' => $assigned->id,
            'reviewer_id' => $reviewer->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
        ]);

        $this->actingAs($reviewer)
            ->get('/papers')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('papers.data', 1)
                ->where('papers.data.0.id', $assigned->id)
                ->where('can.create', false)
            );
    }

    public function test_researcher_can_search_owned_papers_and_filter_by_status(): void
    {
        $researcher = $this->userWithRole(RoleName::Researcher);
        $matching = $this->paperUploadedBy($researcher, 'Explainable Clinical AI');
        $matching->authors()->create(['name' => 'Ada Lovelace', 'author_order' => 1]);
        $this->paperUploadedBy($researcher, 'Unrelated uploaded paper');
        $failed = $this->paperUploadedBy($researcher, 'Explainable failed paper');
        $failed->update(['status' => PaperStatus::Failed]);

        $this->actingAs($researcher)
            ->get('/papers?search=Clinical&status=uploaded')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('papers.data', 1)
                ->where('papers.data.0.id', $matching->id)
                ->where('filters.search', 'Clinical')
                ->where('filters.status', 'uploaded')
            );

        $this->actingAs($researcher)
            ->get('/papers?search=Ada')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('papers.data', 1)
                ->where('papers.data.0.id', $matching->id)
            );
    }

    public function test_library_rejects_invalid_filters_instead_of_broadening_results(): void
    {
        $researcher = $this->userWithRole(RoleName::Researcher);
        $this->paperUploadedBy($researcher, 'Private paper');

        $this->actingAs($researcher)
            ->from('/papers')
            ->get('/papers?status=not-a-status&search='.str_repeat('x', 256))
            ->assertRedirect('/papers')
            ->assertSessionHasErrors(['status', 'search']);
    }

    private function userWithRole(RoleName $roleName): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('name', $roleName->value)->firstOrFail();
        $user->roles()->attach($role);

        return $user;
    }

    private function paperUploadedBy(User $user, string $title): Paper
    {
        return Paper::query()->create([
            'title' => $title,
            'file_path' => 'papers/test/'.str()->uuid().'.pdf',
            'storage_disk' => 'paper-files',
            'original_filename' => 'paper.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 123,
            'checksum_sha256' => str_repeat('b', 64),
            'status' => PaperStatus::Uploaded,
            'uploaded_by' => $user->id,
        ]);
    }

    private function pdf(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            "%PDF-1.7\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n",
        );
    }
}
