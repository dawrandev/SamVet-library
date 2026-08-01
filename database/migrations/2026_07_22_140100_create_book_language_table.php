<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A book with parallel titles is written in more than one language at
     * once. `books.language_id` stays as the single "primary" language (the
     * first one chosen) so every existing filter/stat keeps working
     * unchanged — this pivot additionally records the full set.
     */
    public function up(): void
    {
        Schema::create('book_language', function (Blueprint $table) {
            $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->primary(['book_id', 'language_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_language');
    }
};
