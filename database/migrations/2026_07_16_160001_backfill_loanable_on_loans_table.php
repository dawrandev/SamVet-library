<?php

use App\Models\Loan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every existing row was book-only — a straight 1:1 copy.
        DB::statement("UPDATE loans SET loanable_type = 'book_copy', loanable_id = book_copy_id");

        if (Loan::whereNull('loanable_id')->exists()) {
            throw new RuntimeException('Backfill left loans.loanable_id NULL on some rows — aborting before enforcing NOT NULL.');
        }

        Schema::table('loans', function (Blueprint $table) {
            $table->string('loanable_type')->nullable(false)->change();
            $table->unsignedBigInteger('loanable_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->string('loanable_type')->nullable()->change();
            $table->unsignedBigInteger('loanable_id')->nullable()->change();
        });
    }
};
