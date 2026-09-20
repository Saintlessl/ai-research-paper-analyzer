<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paper_findings', function (Blueprint $table) {
            $table->string('kind')->default('finding')->index()->after('paper_analysis_id');
        });
    }

    public function down(): void
    {
        Schema::table('paper_findings', function (Blueprint $table) {
            $table->dropIndex(['kind']);
            $table->dropColumn('kind');
        });
    }
};
