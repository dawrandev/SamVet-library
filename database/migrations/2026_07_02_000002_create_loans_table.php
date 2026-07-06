<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Loan — a physical copy (book_copy) issued to a reader.
     * Book data (UDC/author/title/year) is obtained via copy→book (not duplicated).
     */
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reader_id')->constrained('readers')->cascadeOnDelete();
            $table->foreignId('book_copy_id')->constrained('book_copies')->cascadeOnDelete();

            $table->date('issued_at');              // Date the book was taken
            $table->date('due_at');                 // Due date for return
            $table->date('returned_at')->nullable(); // Date returned

            $table->string('status')->default('on_loan'); // App\Enums\LoanStatus
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['reader_id', 'status']);
            $table->index(['book_copy_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
