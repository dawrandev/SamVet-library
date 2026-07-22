<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Some books print their title in more than one language on the same
     * title page (a "parallel title" — a standard library-cataloging
     * concept), while still being one catalogued book, not separate editions.
     */
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->json('parallel_titles')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn('parallel_titles');
        });
    }
};
