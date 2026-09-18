<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $timestamp = now();

        DB::table('roles')->insertOrIgnore([
            ['name' => 'researcher', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['name' => 'reviewer', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['name' => 'admin', 'created_at' => $timestamp, 'updated_at' => $timestamp],
        ]);
    }

    public function down(): void
    {
        // Preserve reference data because roles may predate this migration or be assigned to users.
    }
};
