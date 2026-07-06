<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Events and competitions the reader has participated in.
     */
    public function up(): void
    {
        Schema::create('reader_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reader_id')->constrained('readers')->cascadeOnDelete();
            $table->date('date');                 // Date
            $table->string('name');               // Name
            $table->string('place')->nullable();  // Place
            $table->string('type');               // App\Enums\EventType (Competition/Event/...)
            $table->string('role');               // App\Enums\EventRole (Participant/...)
            $table->string('link')->nullable();   // Link
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('reader_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reader_events');
    }
};
