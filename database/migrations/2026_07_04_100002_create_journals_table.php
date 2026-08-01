<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journals', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('kind')->default('journal'); // App\Enums\JournalKind (journal|newspaper)
            $table->string('slug')->unique();

            // Relations (lookup)
            $table->foreignId('journal_type_id')->nullable()->constrained('journal_types')->nullOnDelete();
            $table->string('newspaper_type')->nullable(); // App\Enums\NewspaperType, only when kind=newspaper
            $table->foreignId('language_id')->nullable()->constrained('languages')->nullOnDelete();
            $table->foreignId('publication_place_id')->nullable()->constrained('publication_places')->nullOnDelete();

            $table->string('founder')->nullable();          // Founder
            $table->string('issn')->nullable();
            $table->string('index')->nullable();             // Index (number)

            // Periodicity — e.g. "har 2 haftada 1 marta": unit=week, interval=2, count=1
            $table->string('periodicity_unit')->nullable();     // App\Enums\PeriodicityUnit
            $table->unsignedTinyInteger('periodicity_interval')->nullable();
            $table->unsignedTinyInteger('periodicity_count')->nullable();

            $table->timestamps();

            $table->index('name'); // for search
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journals');
    }
};
