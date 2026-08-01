<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();

            // Bibliographic
            $table->string('title');
            $table->string('authors', 1000)->nullable();       // plain "A. Ism, B. Familiya" text, not a relation
            $table->json('parallel_titles')->nullable();  // title in other editions/languages
            $table->string('slug')->unique();
            $table->string('udc')->nullable();          // UDC (UDK)
            $table->string('author_mark')->nullable();  // Author mark (Cutter)

            // Relations (lookup)
            $table->foreignId('book_type_id')->nullable()->constrained('book_types')->nullOnDelete();
            $table->foreignId('language_id')->nullable()->constrained('languages')->nullOnDelete();

            // Publisher (plain text) and place of publication (lookup)
            $table->string('publisher')->nullable();
            $table->foreignId('publication_place_id')->nullable()->constrained('publication_places')->nullOnDelete();

            // Work group — links editions in different languages (null for a book without translations)
            $table->foreignId('work_id')->nullable()->constrained('works')->nullOnDelete();

            // Publication details
            $table->unsignedSmallInteger('publication_year')->nullable(); // Year
            $table->unsignedInteger('pages')->nullable();                 // Page count
            $table->string('isbn')->nullable();
            $table->unsignedInteger('print_run')->nullable();             // Print run
            $table->text('annotation')->nullable();
            $table->string('target_audience')->nullable();
            $table->unsignedSmallInteger('size_cm')->nullable();
            $table->string('print_sheets')->nullable();
            $table->decimal('price', 12, 2)->nullable();

            // Files
            $table->string('cover_image')->nullable();      // cover image
            $table->string('electronic_file')->nullable();  // Electronic (PDF)

            $table->unsignedBigInteger('views_count')->default(0);

            $table->timestamps();

            $table->index('title'); // for search
            $table->fullText(['title', 'authors', 'annotation', 'udc']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
