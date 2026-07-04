<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            // Ierarxiya: ota menyu (null = yuqori daraja). Ota o'chsa bolalari ham o'chadi.
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $table->json('title'); // tarjima: {"uz":..,"ru":..,"kk":..}
            $table->string('url', 2048)->nullable(); // erkin havola: tashqi URL, ichki yo'l yoki bo'sh
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('target_blank')->default(false);
            $table->timestamps();

            $table->index(['parent_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
