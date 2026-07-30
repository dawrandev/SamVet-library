<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Powers the unified catalog's "smart search" (relevance-ranked MATCH()
 * AGAINST() instead of plain LIKE) — see CatalogRepository::applySmartSearch().
 * One composite FULLTEXT index per table, covering every field the "Barchasi"
 * (default) search scope reads. Pure DDL — no server config changes needed,
 * so this stays deployable through the existing SSH+git-pull pipeline.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->fullText(['title', 'authors', 'annotation', 'udc']);
        });

        Schema::table('audiobooks', function (Blueprint $table) {
            $table->fullText(['name', 'author', 'annotation']);
        });

        Schema::table('videos', function (Blueprint $table) {
            $table->fullText(['name', 'author', 'annotation']);
        });

        Schema::table('dissertations', function (Blueprint $table) {
            $table->fullText(['title', 'author', 'annotation']);
        });

        Schema::table('avtoreferats', function (Blueprint $table) {
            $table->fullText(['title', 'author', 'annotation']);
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropFullText(['title', 'authors', 'annotation', 'udc']);
        });

        Schema::table('audiobooks', function (Blueprint $table) {
            $table->dropFullText(['name', 'author', 'annotation']);
        });

        Schema::table('videos', function (Blueprint $table) {
            $table->dropFullText(['name', 'author', 'annotation']);
        });

        Schema::table('dissertations', function (Blueprint $table) {
            $table->dropFullText(['title', 'author', 'annotation']);
        });

        Schema::table('avtoreferats', function (Blueprint $table) {
            $table->dropFullText(['title', 'author', 'annotation']);
        });
    }
};
