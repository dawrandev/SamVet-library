<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Mutaxassislik shifri va nomi" for a Magistrlik dissertation (e.g. "70710201 - Biotexnologiya").
     * Stored as one combined code+name string per row, same as the librarian's own list.
     */
    public function up(): void
    {
        Schema::create('master_specialties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_specialties');
    }
};
