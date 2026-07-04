<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journals', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();

            // Bog'lanishlar (lookup)
            $table->foreignId('journal_type_id')->nullable()->constrained('journal_types')->nullOnDelete();
            $table->foreignId('language_id')->nullable()->constrained('languages')->nullOnDelete();
            $table->foreignId('publisher_id')->nullable()->constrained('publishers')->nullOnDelete();

            $table->string('founder')->nullable();          // Muassis
            $table->json('publication_place')->nullable();   // Nashr joyi (tarjima)
            $table->string('issn')->nullable();
            $table->string('index')->nullable();             // Indeks (raqam)
            $table->string('periodicity')->nullable();       // App\Enums\JournalPeriodicity

            $table->timestamps();

            $table->index('name'); // qidiruv uchun
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journals');
    }
};
