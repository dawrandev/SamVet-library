<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A dissertation is catalogued like an article: it belongs to a journal
     * issue and carries its own title, author, field and full-text PDF.
     */
    public function up(): void
    {
        Schema::create('dissertations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_issue_id')->constrained('journal_issues')->cascadeOnDelete();

            $table->string('title', 500);   // Dissertation title (single language)
            $table->string('author', 500);  // Free text

            $table->foreignId('resource_field_id')->nullable()->constrained('resource_fields')->nullOnDelete();

            $table->text('annotation')->nullable();

            // Electronic file (PDF) — protected disk (local, NOT public)
            $table->string('electronic_file')->nullable();

            $table->string('slug')->unique();
            $table->unsignedInteger('views_count')->default(0);

            $table->timestamps();

            $table->index('title');
            $table->index('journal_issue_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dissertations');
    }
};
