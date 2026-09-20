<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $adminRole = \App\Models\Role::where('name', 'ADMIN')->first();
        $researcherRole = \App\Models\Role::where('name', 'RESEARCHER')->first();
        $reviewerRole = \App\Models\Role::where('name', 'REVIEWER')->first();

        $admin = \App\Models\User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin User', 'password' => bcrypt('password')]
        );
        $admin->roles()->syncWithoutDetaching([$adminRole->id]);

        $researcher = \App\Models\User::firstOrCreate(
            ['email' => 'researcher@example.com'],
            ['name' => 'Dr. Researcher', 'password' => bcrypt('password')]
        );
        $researcher->roles()->syncWithoutDetaching([$researcherRole->id]);

        $reviewers = [
            ['email' => 'reviewer1@example.com', 'name' => 'Prof. Alan (ML)', 'expertise' => 'Machine Learning, Deep Learning, Neural Networks, Computer Vision'],
            ['email' => 'reviewer2@example.com', 'name' => 'Dr. Bella (NLP)', 'expertise' => 'Natural Language Processing, LLMs, Text Classification, Linguistics'],
            ['email' => 'reviewer3@example.com', 'name' => 'Prof. Charlie (Data)', 'expertise' => 'Data Science, Big Data, Database Systems, Analytics'],
            ['email' => 'reviewer4@example.com', 'name' => 'Dr. Diana (Security)', 'expertise' => 'Cybersecurity, Cryptography, Network Security, Privacy'],
        ];

        foreach ($reviewers as $r) {
            $user = \App\Models\User::firstOrCreate(
                ['email' => $r['email']],
                ['name' => $r['name'], 'password' => bcrypt('password'), 'expertise' => $r['expertise']]
            );
            // Also update expertise if it already existed but was null
            if ($user->expertise !== $r['expertise']) {
                $user->update(['expertise' => $r['expertise']]);
            }
            $user->roles()->syncWithoutDetaching([$reviewerRole->id]);
        }
    }
}
