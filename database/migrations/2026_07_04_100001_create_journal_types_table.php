<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_types', function (Blueprint $table) {
            $table->id();
            $table->json('name'); // translated: {"uz":..,"ru":..,"kk":..}
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_types');
    }
};
