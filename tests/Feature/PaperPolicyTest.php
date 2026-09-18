<?php

namespace Tests\Feature;

use App\Enums\PaperStatus;
use App\Enums\RoleName;
use App\Models\Paper;
use App\Models\ReviewerAssignment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaperPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_researcher_can_manage_only_owned_papers(): void
    {
        $owner = $this->userWithRole(RoleName::Researcher);
        $otherResearcher = $this->userWithRole(RoleName::Researcher);
        $paper = $this->paperUploadedBy($owner);

        $this->assertTrue($owner->can('view', $paper));
        $this->assertTrue($owner->can('viewAnalysis', $paper));
        $this->assertTrue($owner->can('update', $paper));
        $this->assertTrue($owner->can('delete', $paper));
        $this->assertTrue($owner->can('analyze', $paper));

        $this->assertFalse($otherResearcher->can('view', $paper));
        $this->assertFalse($otherResearcher->can('viewAnalysis', $paper));
        $this->assertFalse($otherResearcher->can('update', $paper));
        $this->assertFalse($otherResearcher->can('delete', $paper));
        $this->assertFalse($otherResearcher->can('analyze', $paper));
    }

    public function test_role_controls_paper_listing_and_creation(): void
    {
        $researcher = $this->userWithRole(RoleName::Researcher);
        $reviewer = $this->userWithRole(RoleName::Reviewer);
        $admin = $this->userWithRole(RoleName::Admin);

        $this->assertTrue($researcher->can('viewAny', Paper::class));
        $this->assertTrue($researcher->can('create', Paper::class));
        $this->assertTrue($reviewer->can('viewAny', Paper::class));
        $this->assertFalse($reviewer->can('create', Paper::class));
        $this->assertTrue($admin->can('viewAny', Paper::class));
        $this->assertTrue($admin->can('create', Paper::class));
    }

    public function test_reviewer_can_view_analysis_only_for_assigned_papers(): void
    {
        $owner = $this->userWithRole(RoleName::Researcher);
        $assignedReviewer = $this->userWithRole(RoleName::Reviewer);
        $unassignedReviewer = $this->userWithRole(RoleName::Reviewer);
        $admin = $this->userWithRole(RoleName::Admin);
        $paper = $this->paperUploadedBy($owner);

        ReviewerAssignment::query()->create([
            'paper_id' => $paper->id,
            'reviewer_id' => $assignedReviewer->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
        ]);

        $this->assertTrue($assignedReviewer->can('view', $paper));
        $this->assertTrue($assignedReviewer->can('viewAnalysis', $paper));
        $this->assertFalse($assignedReviewer->can('analyze', $paper));
        $this->assertFalse($assignedReviewer->can('update', $paper));
        $this->assertFalse($assignedReviewer->can('delete', $paper));

        $this->assertFalse($unassignedReviewer->can('view', $paper));
        $this->assertFalse($unassignedReviewer->can('viewAnalysis', $paper));
        $this->assertFalse($unassignedReviewer->can('analyze', $paper));
    }

    public function test_admin_can_perform_every_paper_action(): void
    {
        $owner = $this->userWithRole(RoleName::Researcher);
        $admin = $this->userWithRole(RoleName::Admin);
        $paper = $this->paperUploadedBy($owner);

        foreach (['view', 'viewAnalysis', 'update', 'delete', 'analyze'] as $ability) {
            $this->assertTrue($admin->can($ability, $paper));
        }
    }

    private function userWithRole(RoleName $roleName): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('name', $roleName->value)->firstOrFail();

        $user->roles()->attach($role);

        return $user;
    }

    private function paperUploadedBy(User $user): Paper
    {
        return Paper::query()->create([
            'title' => 'Authorization test paper',
            'file_path' => 'papers/test.pdf',
            'status' => PaperStatus::Uploaded,
            'uploaded_by' => $user->id,
        ]);
    }
}
