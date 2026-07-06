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
            $table->string('slug')->unique();
            $table->string('udc')->nullable();          // UDC (UDK)
            $table->string('author_mark')->nullable();  // Author mark (Cutter)

            // Relations (lookup)
            $table->foreignId('book_type_id')->nullable()->constrained('book_types')->nullOnDelete();
            $table->foreignId('language_id')->nullable()->constrained('languages')->nullOnDelete();
            $table->foreignId('publisher_id')->nullable()->constrained('publishers')->nullOnDelete();

            // Work group — links editions in different languages (null for a book without translations)
            $table->foreignId('work_id')->nullable()->constrained('works')->nullOnDelete();

            // Publication details
            $table->unsignedSmallInteger('publication_year')->nullable(); // Year
            $table->json('publication_place')->nullable();                // Place of publication (translated)
            $table->unsignedInteger('pages')->nullable();                 // Page count
            $table->string('isbn')->nullable();
            $table->unsignedInteger('print_run')->nullable();             // Print run
            $table->text('annotation')->nullable();

            // Files
            $table->string('cover_image')->nullable();      // cover image
            $table->string('electronic_file')->nullable();  // Electronic (PDF)
            $table->string('audio_file')->nullable();       // Audio (mp3)

            $table->unsignedBigInteger('views_count')->default(0);

            $table->timestamps();

            $table->index('title'); // for search
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
