<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $adminRole = Role::where('name', RoleName::Admin->value)->first();
        $researcherRole = Role::where('name', RoleName::Researcher->value)->first();
        $reviewerRole = Role::where('name', RoleName::Reviewer->value)->first();

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin User', 'password' => bcrypt('password'), 'email_verified_at' => now()]
        );
        $admin->roles()->syncWithoutDetaching([$adminRole->id]);

        $researcher = User::firstOrCreate(
            ['email' => 'researcher@example.com'],
            ['name' => 'Dr. Researcher', 'password' => bcrypt('password'), 'email_verified_at' => now()]
        );
        $researcher->roles()->syncWithoutDetaching([$researcherRole->id]);

        $reviewers = [
            ['email' => 'reviewer1@example.com', 'name' => 'Prof. Alan (ML)', 'expertise' => 'Machine Learning, Deep Learning, Neural Networks, Computer Vision'],
            ['email' => 'reviewer2@example.com', 'name' => 'Dr. Bella (NLP)', 'expertise' => 'Natural Language Processing, LLMs, Text Classification, Linguistics'],
            ['email' => 'reviewer3@example.com', 'name' => 'Prof. Charlie (Data)', 'expertise' => 'Data Science, Big Data, Database Systems, Analytics'],
            ['email' => 'reviewer4@example.com', 'name' => 'Dr. Diana (Security)', 'expertise' => 'Cybersecurity, Cryptography, Network Security, Privacy'],
        ];

        foreach ($reviewers as $r) {
            $user = User::firstOrCreate(
                ['email' => $r['email']],
                ['name' => $r['name'], 'password' => bcrypt('password'), 'expertise' => $r['expertise'], 'email_verified_at' => now()]
            );
            if ($user->expertise !== $r['expertise']) {
                $user->update(['expertise' => $r['expertise']]);
            }
            $user->roles()->syncWithoutDetaching([$reviewerRole->id]);
        }
    }
}
