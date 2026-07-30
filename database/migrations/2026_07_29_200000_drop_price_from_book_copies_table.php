<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A single physical copy has no individual price — journal_copies already
 * dropped this same column (2026_07_06_000002_remove_price_from_journal_copies_table.php);
 * book_copies was missed at the time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('book_copies', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }

    public function down(): void
    {
        Schema::table('book_copies', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->nullable();
        });
    }
};
