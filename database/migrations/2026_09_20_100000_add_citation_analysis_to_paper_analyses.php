<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paper_analyses', function (Blueprint $table) {
            $table->json('citation_analysis')->nullable();
            $table->json('ai_suspected_citation_findings')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('paper_analyses', function (Blueprint $table) {
            $table->dropColumn(['citation_analysis', 'ai_suspected_citation_findings']);
        });
    }
};