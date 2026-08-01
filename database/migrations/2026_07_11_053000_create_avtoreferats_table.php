<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An avtoreferat carries its own title, author, defense details and
     * full-text PDF. Inventory/condition/acquisition-disposal acts live on
     * avtoreferat_copies (a title can have several physical copies).
     */
    public function up(): void
    {
        Schema::create('avtoreferats', function (Blueprint $table) {
            $table->id();

            $table->string('title', 500);   // Avtoreferat title (single language)
            $table->string('author', 500)->nullable();  // Free text

            $table->string('specialty')->nullable();
            $table->foreignId('science_field_id')->nullable()->constrained('science_fields')->nullOnDelete(); // Fan nomi
            $table->string('degree')->nullable(); // Turi
            $table->string('council_number')->nullable();
            $table->string('defense_institution')->nullable();
            $table->string('performed_institution')->nullable();
            $table->string('advisor', 500); // Ilmiy rahbari — required
            $table->string('udc')->nullable();
            $table->string('registration_number')->nullable();
            $table->foreignId('publication_place_id')->nullable()->constrained('publication_places')->nullOnDelete(); // Nashr joyi
            $table->unsignedSmallInteger('defense_year')->nullable(); // Himoya yili

            $table->text('annotation')->nullable();
            $table->string('keywords', 500)->nullable();

            // Electronic file (PDF) — protected disk (local, NOT public)
            $table->string('electronic_file')->nullable();

            $table->string('slug')->unique();
            $table->unsignedInteger('views_count')->default(0);

            $table->timestamps();

            $table->index('title');
            $table->fullText(['title', 'author', 'annotation']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avtoreferats');
    }
};
