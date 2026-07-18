<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Degree-conditional bibliographic fields: PhD/DSc dissertations fill
     * science_field_id + doctoral_specialty_id, Magistrlik ones fill
     * master_specialty_id — the rest (advisor, institution, language,
     * publication place, defense year, pages, udc) are shared by both.
     * inventory_number/condition are admin-only (no public dissertation page exists).
     */
    public function up(): void
    {
        Schema::table('dissertations', function (Blueprint $table) {
            $table->string('degree')->nullable()->after('author'); // Turi (PhD/DSc/Magistrlik)

            $table->foreignId('science_field_id')->nullable()->after('resource_field_id')
                ->constrained('science_fields')->nullOnDelete(); // Fan nomi (PhD/DSc)
            $table->foreignId('doctoral_specialty_id')->nullable()->after('science_field_id')
                ->constrained('doctoral_specialties')->nullOnDelete(); // Ixtisoslik shifri va nomi (PhD/DSc)
            $table->foreignId('master_specialty_id')->nullable()->after('doctoral_specialty_id')
                ->constrained('master_specialties')->nullOnDelete(); // Mutaxassislik shifri va nomi (Magistrlik)

            $table->string('advisor', 500)->nullable()->after('master_specialty_id'); // Ilmiy rahbari
            $table->string('institution', 500)->nullable()->after('advisor'); // Muassasi

            $table->foreignId('language_id')->nullable()->after('institution')
                ->constrained('languages')->nullOnDelete(); // Tili
            $table->foreignId('publication_place_id')->nullable()->after('language_id')
                ->constrained('publication_places')->nullOnDelete(); // Nashr joyi

            $table->unsignedSmallInteger('defense_year')->nullable()->after('publication_place_id'); // Himoya yili
            $table->unsignedInteger('pages')->nullable()->after('defense_year'); // Beti
            $table->string('udc')->nullable()->after('pages'); // UO'K

            $table->string('inventory_number')->nullable()->after('udc'); // Inventari — admin-only
            $table->string('condition')->nullable()->after('inventory_number'); // Holati — admin-only
        });
    }

    public function down(): void
    {
        Schema::table('dissertations', function (Blueprint $table) {
            $table->dropForeign(['science_field_id']);
            $table->dropForeign(['doctoral_specialty_id']);
            $table->dropForeign(['master_specialty_id']);
            $table->dropForeign(['language_id']);
            $table->dropForeign(['publication_place_id']);

            $table->dropColumn([
                'degree', 'science_field_id', 'doctoral_specialty_id', 'master_specialty_id',
                'advisor', 'institution', 'language_id', 'publication_place_id',
                'defense_year', 'pages', 'udc', 'inventory_number', 'condition',
            ]);
        });
    }
};
