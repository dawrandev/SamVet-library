<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One video record gathers several individual video files (parts/episodes),
     * each with its own protected file — mirrors audio_tracks/AudioTrack.
     */
    public function up(): void
    {
        Schema::create('video_tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('video_file');   // protected disk (local, NOT public)
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_tracks');
    }
};
