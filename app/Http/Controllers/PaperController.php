<?php

namespace App\Http\Controllers;

use App\Enums\AiJobStatus;
use App\Enums\PaperStatus;
use App\Enums\RoleName;
use App\Http\Requests\StorePaperRequest;
use App\Http\Requests\UpdatePaperRequest;
use App\Jobs\ProcessPaper;
use App\Models\AiJob;
use App\Models\AuditLog;
use App\Models\Paper;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PaperController extends Controller
{
    public function create(): Response
    {
        Gate::authorize('create', Paper::class);

        return Inertia::render('Papers/Create', [
            'maxUploadMegabytes' => (int) config('papers.max_upload_kilobytes') / 1024,
            'maxPublicationYear' => now()->year + 1,
        ]);
    }

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Paper::class);

        /** @var User $user */
        $user = $request->user();
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => [
                'nullable',
                'string',
                Rule::in(array_map(
                    static fn (PaperStatus $status): string => mb_strtolower($status->value),
                    PaperStatus::cases(),
                )),
            ],
        ]);
        $search = trim((string) ($filters['search'] ?? ''));
        $status = isset($filters['status'])
            ? PaperStatus::from(mb_strtoupper($filters['status']))
            : null;
        $isAdmin = $user->hasRole(RoleName::Admin);
        $isResearcher = $user->hasRole(RoleName::Researcher);
        $isReviewer = $user->hasRole(RoleName::Reviewer);
        $papers = Paper::query()
            ->when(! $isAdmin, function (Builder $query) use ($user, $isResearcher, $isReviewer): void {
                $query->where(function (Builder $accessQuery) use ($user, $isResearcher, $isReviewer): void {
                    if ($isResearcher) {
                        $accessQuery->where('uploaded_by', $user->id);
                    }

                    if ($isReviewer) {
                        $method = $isResearcher ? 'orWhereHas' : 'whereHas';
                        $accessQuery->{$method}(
                            'assignments',
                            static fn (Builder $assignments): Builder => $assignments->where('reviewer_id', $user->id),
                        );
                    }
                });
            })
            ->when($search !== '', function (Builder $query) use ($search): void {
                $pattern = "%{$search}%";

                $query->where(function (Builder $searchQuery) use ($pattern): void {
                    $searchQuery
                        ->whereLike('title', $pattern)
                        ->orWhereLike('doi', $pattern)
                        ->orWhereHas('authors', static fn (Builder $authors): Builder => $authors->whereLike('name', $pattern));
                });
            })
            ->when($status !== null, static fn (Builder $query): Builder => $query->where('status', $status->value))
            ->with('authors')
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(static fn (Paper $paper): array => [
                'id' => $paper->id,
                'title' => $paper->title,
                'abstract' => $paper->abstract,
                'publication_year' => $paper->publication_year,
                'journal' => $paper->journal,
                'doi' => $paper->doi,
                'keywords' => $paper->keywords,
                'status' => $paper->status->value,
                'authors' => $paper->authors->map(static fn ($author): array => [
                    'id' => $author->id,
                    'name' => $author->name,
                    'affiliation' => $author->affiliation,
                    'orcid' => $author->orcid,
                    'author_order' => $author->author_order,
                ])->all(),
                'created_at' => $paper->created_at,
                'updated_at' => $paper->updated_at,
            ]);

        return Inertia::render('Papers/Index', [
            'papers' => $papers,
            'filters' => [
                'search' => $search === '' ? null : $search,
                'status' => $status?->value === null ? null : mb_strtolower($status->value),
            ],
            'can' => [
                'create' => Gate::allows('create', Paper::class),
            ],
            'statusOptions' => array_map(
                static fn (PaperStatus $status): array => [
                    'value' => mb_strtolower($status->value),
                    'label' => $status->name,
                ],
                PaperStatus::cases(),
            ),
        ]);
    }

    public function show(\Illuminate\Http\Request $request, Paper $paper): Response
    {
        Gate::authorize('view', $paper);

        $paper->load([
            'authors',
            'analysis',
            'analysis.scores',
            'analysis.findings',
            'references',
            'questions' => fn ($q) => $q->latest(),
            'reviews.reviewer',
        ]);

        // Basic permissions
        $can = [
            'download' => Gate::allows('view', $paper),
            'update' => Gate::allows('update', $paper),
            'delete' => Gate::allows('delete', $paper),
            'review' => Gate::allows('review', $paper),
            'ask' => Gate::allows('askQuestion', $paper), // We'll add this to policy
        ];

        // Format analysis dynamically (ignoring specific schema fields for generic display)
        $analysisData = $paper->analysis ? collect($paper->analysis->toArray())
            ->except(['id', 'paper_id', 'created_at', 'updated_at', 'raw_output', 'citation_analysis', 'ai_suspected_citation_findings'])
            ->filter()
            ->all() : null;

        // Extract specific AI Review if there's a system AI reviewer (mocked for now, Workstream 4 will implement)
        $aiReview = $paper->reviews->firstWhere('reviewer.name', 'AI Reviewer');
        $humanReviews = $paper->reviews->where('reviewer.name', '!==', 'AI Reviewer')->values();

        $reviewers = $request->user()->hasRole('admin') 
            ? \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'REVIEWER'))->get(['id', 'name'])
            : [];

        return Inertia::render('Papers/Show', [
            'paper' => [
                'id' => $paper->id,
                'title' => $paper->title,
                'abstract' => $paper->abstract,
                'publication_year' => $paper->publication_year,
                'journal' => $paper->journal,
                'doi' => $paper->doi,
                'keywords' => $paper->keywords,
                'status' => $paper->status->value,
                'error_message' => $paper->aiJobs()->latest()->first()?->error_message,
                'authors' => $paper->authors->map(static fn ($author): array => [
                    'id' => $author->id,
                    'name' => $author->name,
                    'affiliation' => $author->affiliation,
                    'orcid' => $author->orcid,
                    'author_order' => $author->author_order,
                ])->all(),
                'created_at' => $paper->created_at,
                'updated_at' => $paper->updated_at,
            ],
            'analysis' => $analysisData,
            'scores' => $paper->analysis?->scores,
            'findings' => $paper->analysis?->findings,
            'references' => $paper->references,
            'questions' => $paper->questions,
            'aiReview' => $aiReview,
            'reviews' => $humanReviews,
            'reviewers' => $reviewers,
            'can' => $can,
        ]);
    }

    public function download(Paper $paper): StreamedResponse
    {
        Gate::authorize('view', $paper);

        abort_unless($paper->storage_disk === config('papers.storage_disk'), 409);

        $disk = Storage::disk($paper->storage_disk);

        abort_unless($disk->exists($paper->file_path), 404);

        return $disk->download(
            $paper->file_path,
            "paper-{$paper->id}.pdf",
            [
                'Content-Type' => 'application/pdf',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    public function edit(Paper $paper): Response
    {
        Gate::authorize('update', $paper);

        $paper->load('authors');

        return Inertia::render('Papers/Edit', [
            'paper' => [
                'id' => $paper->id,
                'title' => $paper->title,
                'abstract' => $paper->abstract,
                'publication_year' => $paper->publication_year,
                'journal' => $paper->journal,
                'doi' => $paper->doi,
                'keywords' => $paper->keywords,
                'authors' => $paper->authors->map(static fn ($author): array => [
                    'name' => $author->name,
                    'email' => $author->email,
                    'affiliation' => $author->affiliation,
                    'orcid' => $author->orcid,
                ])->all(),
            ],
            'maxPublicationYear' => now()->year + 1,
        ]);
    }

    public function update(UpdatePaperRequest $request, Paper $paper): RedirectResponse
    {
        $validated = $request->validated();
        /** @var User $user */
        $user = $request->user();

        DB::transaction(function () use ($request, $validated, $user, $paper): void {
            $paper->update([
                'title' => $validated['title'],
                'abstract' => $validated['abstract'] ?? null,
                'publication_year' => $validated['publication_year'] ?? null,
                'journal' => $validated['journal'] ?? null,
                'doi' => $validated['doi'] ?? null,
                'keywords' => $validated['keywords'] ?? null,
            ]);

            $paper->authors()->delete();

            foreach ($validated['authors'] ?? [] as $index => $author) {
                $paper->authors()->create([
                    ...$author,
                    'author_order' => $index + 1,
                ]);
            }

            AuditLog::query()->create([
                'actor_id' => $user->id,
                'action' => 'paper.updated',
                'target_type' => Paper::class,
                'target_id' => $paper->id,
                'metadata' => ['fields' => array_keys($validated)],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return redirect("/papers/{$paper->id}")
            ->with('success', 'Paper metadata updated.');
    }

    public function destroy(Request $request, Paper $paper): RedirectResponse
    {
        Gate::authorize('delete', $paper);

        /** @var User $user */
        $user = $request->user();
        $paperId = $paper->id;
        $title = $paper->title;
        $path = $paper->file_path;
        abort_unless($paper->storage_disk === config('papers.storage_disk'), 409);

        $storage = Storage::disk($paper->storage_disk);
        $backup = null;

        if ($storage->exists($path)) {
            $source = $storage->readStream($path);
            $backup = tmpfile();

            if (! is_resource($source) || ! is_resource($backup)) {
                if (is_resource($source)) {
                    fclose($source);
                }

                if (is_resource($backup)) {
                    fclose($backup);
                }

                throw new RuntimeException('Unable to back up the private PDF before deletion.');
            }

            try {
                if (stream_copy_to_stream($source, $backup) === false || ! rewind($backup)) {
                    throw new RuntimeException('Unable to back up the private PDF before deletion.');
                }
            } catch (Throwable $exception) {
                fclose($backup);
                $backup = null;

                throw $exception;
            } finally {
                fclose($source);
            }
        }

        try {
            DB::transaction(function () use ($request, $user, $paper, $paperId, $title, $storage, $path): void {
                AuditLog::query()->create([
                    'actor_id' => $user->id,
                    'action' => 'paper.deleted',
                    'target_type' => Paper::class,
                    'target_id' => $paperId,
                    'metadata' => ['title' => $title],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                $paper->delete();

                if (! $storage->delete($path)) {
                    throw new RuntimeException('Unable to delete the private PDF.');
                }
            });
        } catch (Throwable $exception) {
            $this->rollBackInterruptedCommit($exception);
            $persisted = Paper::query()->whereKey($paperId)->exists();

            if ($persisted && is_resource($backup) && ! $storage->exists($path)) {
                try {
                    if (! rewind($backup) || ! $storage->writeStream($path, $backup)) {
                        throw new RuntimeException('Private PDF restore failed.');
                    }
                } catch (Throwable) {
                    throw new RuntimeException(
                        'Unable to restore the private PDF after database rollback.',
                        previous: $exception,
                    );
                }
            }

            throw $exception;
        } finally {
            if (is_resource($backup)) {
                fclose($backup);
            }
        }

        return redirect('/papers')->with('success', 'Paper deleted.');
    }

    public function store(StorePaperRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        /** @var UploadedFile $file */
        $file = $validated['file'];
        /** @var User $user */
        $user = $request->user();
        $disk = (string) config('papers.storage_disk');
        $path = sprintf('papers/%d/%s.pdf', $user->id, (string) Str::uuid());
        $checksum = hash_file('sha256', $file->getRealPath());

        if ($checksum === false) {
            throw new RuntimeException('Unable to calculate the uploaded PDF checksum.');
        }

        try {
            $stored = Storage::disk($disk)->putFileAs(
                dirname($path),
                $file,
                basename($path),
            );

            if ($stored === false) {
                throw new RuntimeException('Unable to store the uploaded PDF.');
            }

            $paper = DB::transaction(function () use ($request, $validated, $file, $user, $disk, $path, $checksum): Paper {
                $paper = Paper::query()->create([
                    'title' => $validated['title'],
                    'abstract' => $validated['abstract'] ?? null,
                    'publication_year' => $validated['publication_year'] ?? null,
                    'journal' => $validated['journal'] ?? null,
                    'doi' => $validated['doi'] ?? null,
                    'keywords' => $validated['keywords'] ?? null,
                    'file_path' => $path,
                    'storage_disk' => $disk,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'checksum_sha256' => $checksum,
                    'status' => PaperStatus::Uploaded,
                    'uploaded_by' => $user->id,
                ]);

                foreach ($validated['authors'] ?? [] as $index => $author) {
                    $paper->authors()->create([
                        ...$author,
                        'author_order' => $index + 1,
                    ]);
                }

                AuditLog::query()->create([
                    'actor_id' => $user->id,
                    'action' => 'paper.created',
                    'target_type' => Paper::class,
                    'target_id' => $paper->id,
                    'metadata' => [
                        'status' => PaperStatus::Uploaded->value,
                        'file_size' => $file->getSize(),
                    ],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                $requestId = (string) Str::uuid();
                $aiJob = $paper->aiJobs()->create([
                    'request_id' => $requestId,
                    'user_id' => $user->id,
                    'type' => 'paper.analysis',
                    'status' => AiJobStatus::Pending,
                    'retry_count' => 0,
                    'max_retries' => 3,
                ]);

                AuditLog::query()->create([
                    'actor_id' => $user->id,
                    'action' => 'ai_job.created',
                    'target_type' => AiJob::class,
                    'target_id' => $aiJob->id,
                    'metadata' => ['request_id' => $requestId, 'paper_id' => $paper->id, 'type' => $aiJob->type],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                ProcessPaper::dispatch($aiJob->id)->afterCommit();

                return $paper;
            });
        } catch (Throwable $exception) {
            $this->rollBackInterruptedCommit($exception);
            $persisted = Paper::query()
                ->where('storage_disk', $disk)
                ->where('file_path', $path)
                ->exists();

            if ($persisted) {
                throw $exception;
            }

            try {
                $deleted = Storage::disk($disk)->delete($path);
            } catch (Throwable) {
                throw new RuntimeException(
                    'Unable to clean up the private PDF after upload failure.',
                    previous: $exception,
                );
            }

            if (! $deleted) {
                throw new RuntimeException(
                    'Unable to clean up the private PDF after upload failure.',
                    previous: $exception,
                );
            }

            throw $exception;
        }

        return redirect("/papers/{$paper->id}")
            ->with('success', 'Paper uploaded securely.');
    }

    private function rollBackInterruptedCommit(Throwable $exception): void
    {
        $connection = DB::connection();
        $pdo = $connection->getPdo();

        if ($connection->transactionLevel() !== 0 || ! $pdo->inTransaction()) {
            return;
        }

        try {
            $rolledBack = $pdo->rollBack();
        } catch (Throwable) {
            throw new RuntimeException(
                'Unable to roll back the interrupted database commit.',
                previous: $exception,
            );
        }

        if (! $rolledBack) {
            throw new RuntimeException(
                'Unable to roll back the interrupted database commit.',
                previous: $exception,
            );
        }

        app('db.transactions')->rollback($connection->getName(), 0);
    }
}
