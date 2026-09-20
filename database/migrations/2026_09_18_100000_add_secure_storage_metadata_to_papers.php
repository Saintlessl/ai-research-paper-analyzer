<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('papers', function (Blueprint $table) {
            $table->string('storage_disk')->nullable()->after('file_path');
            $table->char('checksum_sha256', 64)->nullable()->after('file_size');
        });
    }

    public function down(): void
    {
        Schema::table('papers', function (Blueprint $table) {
            $table->dropColumn(['storage_disk', 'checksum_sha256']);
        });
    }
};
