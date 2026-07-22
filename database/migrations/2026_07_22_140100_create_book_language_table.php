<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

        // Backfill: every existing book's current single language becomes its
        // one pivot row, so `languages()` is a consistent source of truth
        // from day one instead of only for books saved after this migration.
        DB::table('books')
            ->whereNotNull('language_id')
            ->select('id', 'language_id')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                DB::table('book_language')->insertOrIgnore(
                    $rows->map(fn ($row) => [
                        'book_id' => $row->id,
                        'language_id' => $row->language_id,
                    ])->all()
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_language');
    }
};
