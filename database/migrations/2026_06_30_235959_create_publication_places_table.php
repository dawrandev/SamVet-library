<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the old `publishers` table as the FK target for books/journals/
 * dissertations/avtoreferats — "place of publication" (e.g. Toshkent),
 * not the publisher itself. Must be created before all four of those.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publication_places', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_places');
    }
};
