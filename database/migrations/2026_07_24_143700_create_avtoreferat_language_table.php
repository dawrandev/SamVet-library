<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An avtoreferat is often written/summarized in more than one language at
 * once (e.g. uz + ru), same idea as book_language for a book's parallel
 * titles. No backfill needed — 0 avtoreferats exist in production.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avtoreferat_language', function (Blueprint $table) {
            $table->foreignId('avtoreferat_id')->constrained('avtoreferats')->cascadeOnDelete();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->primary(['avtoreferat_id', 'language_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avtoreferat_language');
    }
};
