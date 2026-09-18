<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\Paper;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_are_normalized_and_paper_relations_are_available(): void
    {
        $role = Role::query()->where('name', RoleName::Researcher->value)->firstOrFail();
        $user = User::factory()->create();
        $user->roles()->attach($role);
        $paper = Paper::create(['title' => 'Test', 'status' => 'UPLOADED', 'uploaded_by' => $user->id, 'file_path' => 'papers/test.pdf']);
        $paper->authors()->create(['name' => 'Ada Lovelace', 'author_order' => 1]);

        $this->assertTrue($user->hasRole(RoleName::Researcher));
        $this->assertSame('Ada Lovelace', $paper->authors->first()->name);
        $this->assertSame($user->id, $paper->uploader->id);
    }
}
