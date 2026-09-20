<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\Role;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_seeder_is_idempotent_and_creates_only_system_roles(): void
    {
        Role::query()->delete();

        $this->assertDatabaseCount('roles', 0);

        $this->seed(RoleSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->assertSame(
            collect(RoleName::cases())->pluck('value')->sort()->values()->all(),
            Role::query()->pluck('name')->map->value->sort()->values()->all(),
        );
    }

    public function test_database_seeder_creates_system_roles_with_demo_users(): void
    {
        Role::query()->delete();

        $this->assertDatabaseCount('roles', 0);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(
            collect(RoleName::cases())->pluck('value')->sort()->values()->all(),
            Role::query()->pluck('name')->map->value->sort()->values()->all(),
        );
        $this->assertDatabaseCount('users', 6);
    }
}
