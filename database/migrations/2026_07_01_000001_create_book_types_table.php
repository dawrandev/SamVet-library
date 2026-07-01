<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_types', function (Blueprint $table) {
            $table->id();
            $table->json('name'); // tarjima: {"uz":..,"ru":..,"kk":..}
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_types');
    }
};
