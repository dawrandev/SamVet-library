<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One audiobook gathers several individual audio tracks (parts/chapters),
     * each with its own protected file — mirrors how a Journal gathers several
     * JournalIssue records, each with its own file.
     */
    public function up(): void
    {
        Schema::create('audio_tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audiobook_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('audio_file');   // protected disk (local, NOT public)
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audio_tracks');
    }
};
