<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained('journals')->cascadeOnDelete();

            $table->unsignedSmallInteger('year');   // Publication year
            $table->string('issue_number');         // Issue number (e.g. "2024/3")
            $table->unsignedInteger('pages')->nullable();

            // Files
            $table->string('cover_image')->nullable();     // cover (public)
            $table->string('electronic_file')->nullable(); // Electronic (PDF, protected)

            $table->timestamps();

            $table->index(['journal_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_issues');
    }
};
