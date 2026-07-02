<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reader qatnashgan tadbir va tanlovlari.
     */
    public function up(): void
    {
        Schema::create('reader_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reader_id')->constrained('readers')->cascadeOnDelete();
            $table->date('date');                 // Sanasi
            $table->string('name');               // Nomi
            $table->string('place')->nullable();  // Joyi
            $table->string('type');               // App\Enums\EventType (Tanlov/Tadbir/...)
            $table->string('role');               // App\Enums\EventRole (Ishtirokchi/...)
            $table->string('link')->nullable();   // Havola
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
