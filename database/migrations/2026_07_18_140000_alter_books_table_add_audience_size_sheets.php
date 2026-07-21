<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Three fields from the librarian's book-form mockup that didn't exist yet:
     * target audience (free text), physical size (standard cataloging unit — cm,
     * usually the book's height), and print sheet count ("bosma taboq" — kept as
     * free text since it's sometimes written with a comma, e.g. "20,5").
     */
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->string('target_audience')->nullable()->after('annotation'); // Kimlar uchun
            $table->unsignedSmallInteger('size_cm')->nullable()->after('target_audience'); // O'lchami (sm)
            $table->string('print_sheets')->nullable()->after('size_cm'); // Bosma taboq
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn(['target_audience', 'size_cm', 'print_sheets']);
        });
    }
};
