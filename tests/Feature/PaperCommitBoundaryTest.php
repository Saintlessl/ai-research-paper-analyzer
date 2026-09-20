<?php

namespace Tests\Feature;

use App\Enums\PaperStatus;
use App\Enums\RoleName;
use App\Models\Paper;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionCommitting;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaperCommitBoundaryTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Storage::fake('paper-files');
        Queue::fake();
    }

    public function test_upload_rolls_back_and_removes_the_private_pdf_when_commit_is_interrupted(): void
    {
        $researcher = $this->researcher();
        $events = DB::connection()->getEventDispatcher();
        $events->listen(TransactionCommitting::class, static function (): never {
            throw new \RuntimeException('Forced failure before database commit.');
        });
        $caught = null;
        $transactionLeaked = false;

        try {
            $this->withoutExceptionHandling();
            $this->actingAs($researcher)->post('/papers', [
                'title' => 'Interrupted upload',
                'file' => $this->pdf('interrupted-upload.pdf'),
            ]);
            $this->fail('The pre-commit failure was not thrown.');
        } catch (\RuntimeException $exception) {
            $caught = $exception;
        } finally {
            $events->forget(TransactionCommitting::class);
            $transactionLeaked = DB::connection()->getPdo()->inTransaction();
            $this->rollbackLeakedPdoTransaction();
        }

        $this->assertSame('Forced failure before database commit.', $caught?->getMessage());
        $this->assertFalse($transactionLeaked);
        $this->assertDatabaseCount('papers', 0);
        $this->assertDatabaseCount('audit_logs', 0);
        $this->assertSame([], Storage::disk('paper-files')->allFiles());
    }

    public function test_delete_rolls_back_and_restores_the_private_pdf_when_commit_is_interrupted(): void
    {
        $researcher = $this->researcher();
        $paper = Paper::query()->create([
            'title' => 'Interrupted deletion',
            'file_path' => 'papers/interrupted-deletion.pdf',
            'storage_disk' => 'paper-files',
            'status' => PaperStatus::Uploaded,
            'uploaded_by' => $researcher->id,
        ]);
        Storage::disk('paper-files')->put($paper->file_path, 'private PDF');
        $events = DB::connection()->getEventDispatcher();
        $events->listen(TransactionCommitting::class, static function (): never {
            throw new \RuntimeException('Forced failure before database commit.');
        });
        $caught = null;
        $transactionLeaked = false;

        try {
            $this->withoutExceptionHandling();
            $this->actingAs($researcher)->delete("/papers/{$paper->id}");
            $this->fail('The pre-commit failure was not thrown.');
        } catch (\RuntimeException $exception) {
            $caught = $exception;
        } finally {
            $events->forget(TransactionCommitting::class);
            $transactionLeaked = DB::connection()->getPdo()->inTransaction();
            $this->rollbackLeakedPdoTransaction();
        }

        $this->assertSame('Forced failure before database commit.', $caught?->getMessage());
        $this->assertFalse($transactionLeaked);
        $this->assertDatabaseHas('papers', ['id' => $paper->id]);
        $this->assertDatabaseCount('audit_logs', 0);
        Storage::disk('paper-files')->assertExists($paper->file_path, 'private PDF');
    }

    public function test_upload_keeps_the_private_pdf_when_an_exception_occurs_after_pdo_commit(): void
    {
        $researcher = $this->researcher();
        $events = DB::connection()->getEventDispatcher();
        $events->listen(TransactionCommitted::class, static function (): never {
            throw new \RuntimeException('Forced failure after database commit.');
        });
        $caught = null;

        try {
            $this->withoutExceptionHandling();
            $this->actingAs($researcher)->post('/papers', [
                'title' => 'Committed upload',
                'file' => $this->pdf('committed-upload.pdf'),
            ]);
            $this->fail('The post-commit failure was not thrown.');
        } catch (\RuntimeException $exception) {
            $caught = $exception;
        } finally {
            $events->forget(TransactionCommitted::class);
        }

        $this->assertSame('Forced failure after database commit.', $caught?->getMessage());
        $this->assertFalse(DB::connection()->getPdo()->inTransaction());
        $paper = Paper::query()->where('title', 'Committed upload')->sole();
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'paper.created',
            'target_id' => $paper->id,
        ]);
        Storage::disk('paper-files')->assertExists($paper->file_path);
    }

    public function test_delete_does_not_restore_the_private_pdf_after_pdo_commit(): void
    {
        $researcher = $this->researcher();
        $paper = Paper::query()->create([
            'title' => 'Committed deletion',
            'file_path' => 'papers/committed-deletion.pdf',
            'storage_disk' => 'paper-files',
            'status' => PaperStatus::Uploaded,
            'uploaded_by' => $researcher->id,
        ]);
        Storage::disk('paper-files')->put($paper->file_path, 'private PDF');
        $events = DB::connection()->getEventDispatcher();
        $events->listen(TransactionCommitted::class, static function (): never {
            throw new \RuntimeException('Forced failure after database commit.');
        });
        $caught = null;

        try {
            $this->withoutExceptionHandling();
            $this->actingAs($researcher)->delete("/papers/{$paper->id}");
            $this->fail('The post-commit failure was not thrown.');
        } catch (\RuntimeException $exception) {
            $caught = $exception;
        } finally {
            $events->forget(TransactionCommitted::class);
        }

        $this->assertSame('Forced failure after database commit.', $caught?->getMessage());
        $this->assertFalse(DB::connection()->getPdo()->inTransaction());
        $this->assertDatabaseMissing('papers', ['id' => $paper->id]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'paper.deleted',
            'target_id' => $paper->id,
        ]);
        Storage::disk('paper-files')->assertMissing($paper->file_path);
    }

    private function researcher(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(
            Role::query()->where('name', RoleName::Researcher->value)->sole(),
        );

        return $user;
    }

    private function pdf(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            "%PDF-1.7\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n",
        );
    }

    private function rollbackLeakedPdoTransaction(): void
    {
        $pdo = DB::connection()->getPdo();

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
}
