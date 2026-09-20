<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthorizationPropsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_public_pages_receive_an_anonymous_fail_closed_auth_payload(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.user', null)
                ->where('auth.capabilities.upload_papers', false)
                ->where('auth.capabilities.review_papers', false)
                ->where('auth.capabilities.manage_system', false)
            );
    }

    public function test_authenticated_pages_receive_server_derived_role_and_capabilities(): void
    {
        $admin = User::factory()->create();
        $adminRole = Role::query()->where('name', RoleName::Admin->value)->firstOrFail();
        $admin->roles()->attach($adminRole);

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.user.role', RoleName::Admin->value)
                ->where('auth.user.roles', [RoleName::Admin->value])
                ->where('auth.capabilities.upload_papers', true)
                ->where('auth.capabilities.review_papers', true)
                ->where('auth.capabilities.manage_system', true)
            );
    }

    public function test_researcher_capabilities_do_not_include_privileged_actions(): void
    {
        $researcher = User::factory()->create();
        $researcherRole = Role::query()->where('name', RoleName::Researcher->value)->firstOrFail();
        $researcher->roles()->attach($researcherRole);

        $this->actingAs($researcher)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.user.role', RoleName::Researcher->value)
                ->where('auth.capabilities.upload_papers', true)
                ->where('auth.capabilities.review_papers', false)
                ->where('auth.capabilities.manage_system', false)
            );
    }

    public function test_multi_role_users_receive_deterministic_primary_role_and_combined_capabilities(): void
    {
        $user = User::factory()->create();
        $roleIds = Role::query()
            ->whereIn('name', [RoleName::Researcher->value, RoleName::Reviewer->value])
            ->pluck('id');

        $user->roles()->attach($roleIds);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.user.role', RoleName::Reviewer->value)
                ->where('auth.capabilities.upload_papers', true)
                ->where('auth.capabilities.review_papers', true)
                ->where('auth.capabilities.manage_system', false)
            );
    }
}
