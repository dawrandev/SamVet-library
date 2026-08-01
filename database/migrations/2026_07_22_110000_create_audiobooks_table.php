<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audiobooks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('author')->nullable();
            $table->date('published_at')->nullable();
            $table->text('annotation')->nullable();
            $table->string('cover_image')->nullable();   // public disk
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();

            $table->index('name'); // for search
            $table->fullText(['name', 'author', 'annotation']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audiobooks');
    }
};
