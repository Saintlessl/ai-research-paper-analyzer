<?php

namespace Tests\Feature;

use App\Enums\PaperStatus;
use App\Enums\RoleName;
use App\Models\Paper;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class PaperDeletionAtomicityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Storage::fake('paper-files');
    }

    public function test_delete_restores_the_private_pdf_when_the_transaction_fails_after_storage_deletion(): void
    {
        $researcher = User::factory()->create();
        $researcher->roles()->attach(
            Role::query()->where('name', RoleName::Researcher->value)->sole(),
        );
        $paper = Paper::query()->create([
            'title' => 'Paper with failed database commit',
            'file_path' => 'papers/commit-failure.pdf',
            'storage_disk' => 'paper-files',
            'status' => PaperStatus::Uploaded,
            'uploaded_by' => $researcher->id,
        ]);
        $originalDisk = Storage::disk('paper-files');
        $originalDisk->put($paper->file_path, 'private PDF');
        $source = fopen('php://temp', 'w+b');
        fwrite($source, 'private PDF');
        rewind($source);
        $failingDisk = Mockery::mock(FilesystemAdapter::class);
        $failingDisk->shouldReceive('exists')
            ->twice()
            ->with($paper->file_path)
            ->andReturn(true, false);
        $failingDisk->shouldReceive('readStream')
            ->once()
            ->with($paper->file_path)
            ->andReturn($source);
        $failingDisk->shouldReceive('delete')
            ->once()
            ->with($paper->file_path)
            ->andReturnUsing(function (string $path) use ($originalDisk): never {
                $originalDisk->delete($path);

                throw new \RuntimeException('Forced transaction failure after storage deletion.');
            });
        $failingDisk->shouldReceive('writeStream')
            ->once()
            ->with($paper->file_path, Mockery::type('resource'))
            ->andReturnUsing(function (string $path, $stream) use ($originalDisk): bool {
                return $originalDisk->writeStream($path, $stream);
            });
        Storage::getFacadeRoot()->set('paper-files', $failingDisk);

        try {
            $this->withoutExceptionHandling();
            $this->actingAs($researcher)->delete("/papers/{$paper->id}");
            $this->fail('The forced transaction failure was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Forced transaction failure after storage deletion.', $exception->getMessage());
        } finally {
            Storage::getFacadeRoot()->set('paper-files', $originalDisk);
        }

        $this->assertDatabaseHas('papers', ['id' => $paper->id]);
        $this->assertDatabaseCount('audit_logs', 0);
        $originalDisk->assertExists($paper->file_path, 'private PDF');
    }

    public function test_delete_reports_restore_failure_without_hiding_the_transaction_failure(): void
    {
        $researcher = User::factory()->create();
        $researcher->roles()->attach(
            Role::query()->where('name', RoleName::Researcher->value)->sole(),
        );
        $paper = Paper::query()->create([
            'title' => 'Paper with failed restore',
            'file_path' => 'papers/restore-failure.pdf',
            'storage_disk' => 'paper-files',
            'status' => PaperStatus::Uploaded,
            'uploaded_by' => $researcher->id,
        ]);
        $originalDisk = Storage::disk('paper-files');
        $originalDisk->put($paper->file_path, 'private PDF');
        $source = fopen('php://temp', 'w+b');
        fwrite($source, 'private PDF');
        rewind($source);
        $failingDisk = Mockery::mock(FilesystemAdapter::class);
        $failingDisk->shouldReceive('exists')
            ->twice()
            ->with($paper->file_path)
            ->andReturn(true, false);
        $failingDisk->shouldReceive('readStream')
            ->once()
            ->with($paper->file_path)
            ->andReturn($source);
        $failingDisk->shouldReceive('delete')
            ->once()
            ->with($paper->file_path)
            ->andReturnUsing(function (string $path) use ($originalDisk): never {
                $originalDisk->delete($path);

                throw new \RuntimeException('Forced transaction failure after storage deletion.');
            });
        $failingDisk->shouldReceive('writeStream')
            ->once()
            ->with($paper->file_path, Mockery::type('resource'))
            ->andThrow(new \RuntimeException('Forced private PDF restore failure.'));
        Storage::getFacadeRoot()->set('paper-files', $failingDisk);

        try {
            $this->withoutExceptionHandling();
            $this->actingAs($researcher)->delete("/papers/{$paper->id}");
            $this->fail('The restore failure was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Unable to restore the private PDF after database rollback.', $exception->getMessage());
            $this->assertSame(
                'Forced transaction failure after storage deletion.',
                $exception->getPrevious()?->getMessage(),
            );
        } finally {
            Storage::getFacadeRoot()->set('paper-files', $originalDisk);
        }

        $this->assertDatabaseHas('papers', ['id' => $paper->id]);
        $this->assertDatabaseCount('audit_logs', 0);
        $originalDisk->assertMissing($paper->file_path);
    }

    public function test_delete_does_not_restore_the_private_pdf_after_database_commit(): void
    {
        $researcher = User::factory()->create();
        $researcher->roles()->attach(
            Role::query()->where('name', RoleName::Researcher->value)->sole(),
        );
        $paper = Paper::query()->create([
            'title' => 'Committed deletion',
            'file_path' => 'papers/committed-deletion.pdf',
            'storage_disk' => 'paper-files',
            'status' => PaperStatus::Uploaded,
            'uploaded_by' => $researcher->id,
        ]);
        Storage::disk('paper-files')->put($paper->file_path, 'private PDF');
        \Illuminate\Support\Facades\Queue::fake([\App\Jobs\DeletePaperVectorJob::class]);
        $events = DB::connection()->getEventDispatcher();
        $events->listen(TransactionCommitted::class, static function (): never {
            throw new \RuntimeException('Forced failure after database commit.');
        });

        try {
            $this->withoutExceptionHandling();
            $this->actingAs($researcher)->delete("/papers/{$paper->id}");
            $this->fail('The post-commit failure was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Forced failure after database commit.', $exception->getMessage());
        } finally {
            $events->forget(TransactionCommitted::class);
        }

        $this->assertDatabaseMissing('papers', ['id' => $paper->id]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'paper.deleted',
            'target_id' => $paper->id,
        ]);
        Storage::disk('paper-files')->assertMissing($paper->file_path);
    }
}
