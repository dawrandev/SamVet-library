<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the draft/published status from news — every news item is visible once created.
 * `published_at` is kept as the plain publish date.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropIndex(['status', 'published_at']); // composite index uses the column
            $table->dropColumn('status');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropIndex(['published_at']);
            $table->string('status')->default('draft')->after('cover_image');
            $table->index(['status', 'published_at']);
        });
    }
};
