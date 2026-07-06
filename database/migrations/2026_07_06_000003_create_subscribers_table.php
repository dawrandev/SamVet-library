<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();

            $table->string('full_name');
            $table->string('position')->nullable();     // job title / position
            $table->string('department')->nullable();    // department / chair
            $table->string('phone')->nullable();

            $table->timestamps();

            $table->index('full_name'); // for search
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscribers');
    }
};
