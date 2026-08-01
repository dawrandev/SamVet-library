<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_issue_id')->nullable()->constrained('journal_issues')->cascadeOnDelete();
            // An article from a journal this library doesn't hold an issue of.
            $table->string('external_journal_name')->nullable();
            $table->string('external_journal_issue')->nullable();

            $table->string('title', 500);   // Single language (like a book title)
            $table->string('author', 500)->nullable();  // Free text (multiple authors, comma separated)

            // Article-specific lookups (may differ from the parent journal)
            $table->foreignId('resource_field_id')->nullable()->constrained('resource_fields')->nullOnDelete();
            $table->string('category')->nullable();
            $table->foreignId('language_id')->nullable()->constrained('languages')->nullOnDelete();

            $table->string('doi')->nullable();
            $table->string('pages', 50)->nullable(); // e.g. "45-52"
            $table->text('annotation')->nullable();

            // Electronic file (PDF) — protected disk (local, NOT public)
            $table->string('electronic_file')->nullable();

            $table->string('slug')->unique();
            $table->unsignedInteger('views_count')->default(0);

            $table->timestamps();

            $table->index('title');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
