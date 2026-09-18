<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        Route::middleware(['web', 'auth', 'role:admin'])
            ->get('/_test/admin-zone', fn () => response()->json(['ok' => true]));
    }

    public function test_user_without_required_role_is_forbidden(): void
    {
        $researcher = $this->userWithRole(RoleName::Researcher);

        $this->actingAs($researcher)
            ->get('/_test/admin-zone')
            ->assertForbidden();
    }

    public function test_user_with_required_role_is_allowed(): void
    {
        $admin = $this->userWithRole(RoleName::Admin);

        $this->actingAs($admin)
            ->get('/_test/admin-zone')
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_authenticated_user_without_a_role_cannot_access_dashboard(): void
    {
        $rolelessUser = User::factory()->create();

        $this->actingAs($rolelessUser)
            ->get('/dashboard')
            ->assertForbidden();
    }

    private function userWithRole(RoleName $roleName): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('name', $roleName->value)->firstOrFail();

        $user->roles()->attach($role);

        return $user;
    }
}
