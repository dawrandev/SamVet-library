<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropForeign(['book_copy_id']);
            $table->dropIndex(['book_copy_id', 'status']);
            $table->dropColumn('book_copy_id');

            // Time is now captured too, not just the date (both auto-set by the server).
            $table->dateTime('issued_at')->nullable(false)->change();
            $table->dateTime('due_at')->nullable(false)->change();
            $table->dateTime('returned_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->foreignId('book_copy_id')->nullable()->after('loanable_id')->constrained('book_copies')->cascadeOnDelete();
        });

        DB::statement("UPDATE loans SET book_copy_id = loanable_id WHERE loanable_type = 'book_copy'");

        Schema::table('loans', function (Blueprint $table) {
            $table->date('issued_at')->change();
            $table->date('due_at')->change();
            $table->date('returned_at')->nullable()->change();
            $table->index(['book_copy_id', 'status']);
        });
    }
};
