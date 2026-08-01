<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A dissertation carries its own title, author, degree-conditional
     * specialty and full-text PDF. Degree-conditional bibliographic fields:
     * PhD/DSc dissertations fill science_field_id + doctoral_specialty_id,
     * Magistrlik ones fill master_specialty_id — the rest (advisor,
     * institution, language, publication place, defense year, pages, udc)
     * are shared by both. inventory_number/condition/acts are admin-only
     * (no public dissertation page exposes them).
     */
    public function up(): void
    {
        Schema::create('dissertations', function (Blueprint $table) {
            $table->id();

            $table->string('title', 500);   // Dissertation title (single language)
            $table->string('author', 500)->nullable();  // Free text

            $table->string('degree')->nullable(); // Turi (PhD/DSc/Magistrlik)

            $table->foreignId('science_field_id')->nullable()->constrained('science_fields')->nullOnDelete(); // Fan nomi (PhD/DSc)
            $table->foreignId('doctoral_specialty_id')->nullable()->constrained('doctoral_specialties')->nullOnDelete(); // Ixtisoslik shifri va nomi (PhD/DSc)
            $table->foreignId('master_specialty_id')->nullable()->constrained('master_specialties')->nullOnDelete(); // Mutaxassislik shifri va nomi (Magistrlik)

            $table->string('advisor', 500)->nullable(); // Ilmiy rahbari
            $table->string('institution', 500)->nullable(); // Muassasi

            $table->foreignId('language_id')->nullable()->constrained('languages')->nullOnDelete(); // Tili
            $table->foreignId('publication_place_id')->nullable()->constrained('publication_places')->nullOnDelete(); // Nashr joyi

            $table->unsignedSmallInteger('defense_year')->nullable(); // Himoya yili
            $table->unsignedInteger('pages')->nullable(); // Beti
            $table->string('udc')->nullable(); // UO'K

            // Admin-only inventory/condition/acts
            $table->string('inventory_number')->nullable();
            $table->string('acquisition_act_number')->nullable();
            $table->date('acquisition_act_at')->nullable();
            $table->string('disposal_act_number')->nullable();
            $table->date('disposal_act_at')->nullable();
            $table->text('condition')->nullable();

            $table->text('annotation')->nullable();

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
        Schema::dropIfExists('dissertations');
    }
};
