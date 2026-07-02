<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Oldi-berdi — reader'ga berilgan jismoniy nusxa (book_copy).
     * Kitob ma'lumotlari (UO'K/muallif/sarlavha/yil) nusxa→kitob orqali olinadi (dublikat emas).
     */
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reader_id')->constrained('readers')->cascadeOnDelete();
            $table->foreignId('book_copy_id')->constrained('book_copies')->cascadeOnDelete();

            $table->date('issued_at');              // Kitobni olgan sana
            $table->date('due_at');                 // Qaytarish muddati
            $table->date('returned_at')->nullable(); // Qaytargan sana

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
