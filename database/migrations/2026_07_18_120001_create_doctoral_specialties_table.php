<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Ixtisoslik shifri va nomi" for a PhD/DSc dissertation (e.g. "03.00.06-Zoologiya").
     * Stored as one combined code+name string per row, same as the librarian's own list.
     */
    public function up(): void
    {
        Schema::create('doctoral_specialties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctoral_specialties');
    }
};
