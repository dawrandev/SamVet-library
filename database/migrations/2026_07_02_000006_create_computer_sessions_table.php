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
            $table->dateTime('issued_at');
            $table->dateTime('returned_at')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->foreignId('computer_id')->constrained('computers')->restrictOnDelete();
            $table->string('location')->nullable();     // Location
            $table->string('purpose')->nullable();      // Purpose of use
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('reader_id');
            $table->index('expires_at');
            $table->index('returned_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('computer_sessions');
    }
};
