<?php

namespace Tests\Feature;

use App\Enums\PaperStatus;
use App\Enums\RoleName;
use App\Models\Paper;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaperAuthorizationHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Storage::fake('paper-files');
        config()->set('papers.storage_disk', 'paper-files');
    }

    public function test_unverified_researcher_cannot_access_paper_management_routes(): void
    {
        $researcher = $this->userWithRole(RoleName::Researcher, verified: false);

        $this->actingAs($researcher)
            ->get('/papers')
            ->assertRedirect('/verify-email');

        $this->actingAs($researcher)
            ->get('/papers/create')
            ->assertRedirect('/verify-email');

        $this->actingAs($researcher)
            ->post('/papers', [
                'title' => 'Unverified upload',
                'file' => $this->pdf(),
            ])
            ->assertRedirect('/verify-email');

        $this->assertDatabaseCount('papers', 0);
        $this->assertSame([], Storage::disk('paper-files')->allFiles());
    }

    public function test_reviewer_cannot_open_or_submit_the_upload_form(): void
    {
        $reviewer = $this->userWithRole(RoleName::Reviewer);

        $this->actingAs($reviewer)
            ->get('/papers/create')
            ->assertForbidden();

        $this->actingAs($reviewer)
            ->post('/papers', [
                'title' => 'Reviewer upload attempt',
                'file' => $this->pdf(),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('papers', 0);
        $this->assertDatabaseCount('audit_logs', 0);
        $this->assertSame([], Storage::disk('paper-files')->allFiles());
    }

    public function test_non_owner_cannot_read_or_mutate_another_researchers_paper(): void
    {
        $owner = $this->userWithRole(RoleName::Researcher);
        $otherResearcher = $this->userWithRole(RoleName::Researcher);
        $paper = $this->paperUploadedBy($owner);
        Storage::disk('paper-files')->put($paper->file_path, 'private PDF');

        $this->actingAs($otherResearcher)->get("/papers/{$paper->id}")->assertForbidden();
        $this->actingAs($otherResearcher)->get("/papers/{$paper->id}/edit")->assertForbidden();
        $this->actingAs($otherResearcher)->get("/papers/{$paper->id}/download")->assertForbidden();
        $this->actingAs($otherResearcher)->patch("/papers/{$paper->id}", [
            'title' => 'Unauthorized update',
        ])->assertForbidden();
        $this->actingAs($otherResearcher)->delete("/papers/{$paper->id}")->assertForbidden();

        $this->assertSame('Owner paper', $paper->fresh()?->title);
        Storage::disk('paper-files')->assertExists($paper->file_path);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    private function userWithRole(RoleName $roleName, bool $verified = true): User
    {
        $user = User::factory()
            ->when(! $verified, static fn ($factory) => $factory->unverified())
            ->create();
        $role = Role::query()->where('name', $roleName->value)->firstOrFail();
        $user->roles()->attach($role);

        return $user;
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'paper.pdf',
            "%PDF-1.7\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n",
        );
    }

    private function paperUploadedBy(User $user): Paper
    {
        return Paper::query()->create([
            'title' => 'Owner paper',
            'file_path' => 'papers/test/owner.pdf',
            'storage_disk' => 'paper-files',
            'original_filename' => 'paper.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 11,
            'checksum_sha256' => str_repeat('a', 64),
            'status' => PaperStatus::Uploaded,
            'uploaded_by' => $user->id,
        ]);
    }
}
