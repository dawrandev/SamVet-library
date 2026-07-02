<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reader kompyuterdan foydalanishi.
     */
    public function up(): void
    {
        Schema::create('computer_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reader_id')->constrained('readers')->cascadeOnDelete();
            $table->date('date');                       // Sanasi
            $table->time('issued_time')->nullable();    // Berilgan vaqti
            $table->time('returned_time')->nullable();  // Topshirish vaqti
            $table->string('computer_number')->nullable(); // Kompyuter raqami
            $table->string('location')->nullable();     // Joylashuv
            $table->string('purpose')->nullable();      // Foydalanish maqsadi
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('reader_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('computer_sessions');
    }
};
