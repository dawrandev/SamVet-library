<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            // Polymorphic target (BookCopy or JournalCopy) — replaces book_copy_id.
            $table->string('loanable_type')->nullable()->after('reader_id');
            $table->unsignedBigInteger('loanable_id')->nullable()->after('loanable_type');
            $table->index(['loanable_type', 'loanable_id']);

            // Condition snapshots — captured at issue time, recorded at return time.
            $table->string('issued_condition')->nullable()->after('note');
            $table->string('returned_condition')->nullable()->after('issued_condition');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropIndex(['loanable_type', 'loanable_id']);
            $table->dropColumn(['loanable_type', 'loanable_id', 'issued_condition', 'returned_condition']);
        });
    }
};
