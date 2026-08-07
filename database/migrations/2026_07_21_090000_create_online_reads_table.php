<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One online read/watch/listen session by a signed-in reader — an
 * append-only log, never updated. Polymorphic (`readable`) so it covers
 * Book, Video, Audiobook, Dissertation and Avtoreferat alike, the same
 * pattern loans already use for loanable copies.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reader_id')->constrained('readers')->cascadeOnDelete();
            $table->morphs('readable');
            $table->timestamp('read_at');

            $table->index(['reader_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_reads');
    }
};
