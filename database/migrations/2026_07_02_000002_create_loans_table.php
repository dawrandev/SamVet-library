<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Loan — a physical copy issued to a reader. Polymorphic (`loanable`)
     * since a copy can be a BookCopy or (in the future) another loanable
     * type. Book data (UDC/author/title/year) is obtained via copy→book
     * (not duplicated).
     */
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reader_id')->constrained('readers')->cascadeOnDelete();
            $table->morphs('loanable');

            $table->dateTime('issued_at');              // Date the book was taken
            $table->dateTime('due_at');                 // Due date for return
            $table->dateTime('returned_at')->nullable(); // Date returned

            $table->string('status')->default('on_loan'); // App\Enums\LoanStatus
            $table->text('note')->nullable();
            $table->text('issued_condition')->nullable();
            $table->text('returned_condition')->nullable();

            $table->timestamps();

            $table->index(['reader_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
