<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            // A book title's acquisition price — feeds the annual expenses
            // report (Yillik xarajatlar hisoboti). One price per title, not
            // per copy: book_copies.price was deliberately removed twice
            // (see 2026_07_29_200000_drop_price_from_book_copies_table.php)
            // because a single physical copy has no individually tracked
            // price in this project's model.
            $table->decimal('price', 12, 2)->nullable()->after('print_sheets');
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
};
