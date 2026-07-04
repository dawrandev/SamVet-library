<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained('journals')->cascadeOnDelete();

            $table->unsignedSmallInteger('year');   // Nashr yili
            $table->string('issue_number');         // Soni (masalan: "2024/3")
            $table->unsignedInteger('pages')->nullable();

            // Fayllar
            $table->string('cover_image')->nullable();     // muqova (ochiq)
            $table->string('electronic_file')->nullable(); // Elektron (PDF, himoyalangan)

            $table->timestamps();

            $table->index(['journal_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_issues');
    }
};
