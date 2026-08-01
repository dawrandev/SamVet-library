<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            // Each page is tied to exactly one menu item.
            $table->foreignId('menu_item_id')->unique()
                ->constrained('menu_items')->cascadeOnDelete();
            // Translated (uz/ru/kk). Falls back to the menu item's own title when empty.
            $table->json('title')->nullable();
            $table->json('body')->nullable();    // rich HTML (translated)
            $table->string('cover_image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
