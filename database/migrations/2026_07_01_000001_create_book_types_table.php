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
            $table->string('name'); // Darslik, O'quv qo'llanma, Uslubiy qo'llanma...
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_types');
    }
};
