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

            // Bibliografik
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('udc')->nullable();          // UO'K (UDK)
            $table->string('author_mark')->nullable();  // Avtorlik belgi (katter)

            // Bog'lanishlar (lookup)
            $table->foreignId('book_type_id')->nullable()->constrained('book_types')->nullOnDelete();
            $table->foreignId('language_id')->nullable()->constrained('languages')->nullOnDelete();
            $table->foreignId('publisher_id')->nullable()->constrained('publishers')->nullOnDelete();

            // Asar guruhi — turli tildagi nashrlarni bog'laydi (tarjimasiz kitobда null)
            $table->foreignId('work_id')->nullable()->constrained('works')->nullOnDelete();

            // Nashr ma'lumotlari
            $table->unsignedSmallInteger('publication_year')->nullable(); // Yili
            $table->json('publication_place')->nullable();                // Nashriyot joyi (tarjima)
            $table->unsignedInteger('pages')->nullable();                 // Beti
            $table->string('isbn')->nullable();
            $table->unsignedInteger('print_run')->nullable();             // Tiraj
            $table->text('annotation')->nullable();

            // Fayllar
            $table->string('cover_image')->nullable();      // muqova rasmi
            $table->string('electronic_file')->nullable();  // Elektron (PDF)
            $table->string('audio_file')->nullable();       // Audio (mp3)

            $table->boolean('has_continuation')->default(false); // Davomi bor
            $table->unsignedBigInteger('views_count')->default(0);

            $table->timestamps();

            $table->index('title'); // qidiruv uchun
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
