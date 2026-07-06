<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A reader's use of a computer.
     */
    public function up(): void
    {
        Schema::create('computer_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reader_id')->constrained('readers')->cascadeOnDelete();
            $table->date('date');                       // Date
            $table->time('issued_time')->nullable();    // Time issued
            $table->time('returned_time')->nullable();  // Time returned
            $table->string('computer_number')->nullable(); // Computer number
            $table->string('location')->nullable();     // Location
            $table->string('purpose')->nullable();      // Purpose of use
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
