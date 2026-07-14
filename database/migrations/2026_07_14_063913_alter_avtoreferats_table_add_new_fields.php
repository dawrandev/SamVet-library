<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An avtoreferat is now a standalone document (no longer tied to a
     * journal issue) with its own dissertation-defense bibliographic fields.
     */
    public function up(): void
    {
        Schema::table('avtoreferats', function (Blueprint $table) {
            $table->dropForeign(['journal_issue_id']);
            $table->dropColumn('journal_issue_id');

            $table->string('specialty')->nullable()->after('author');            // Ixtisoslik shifri va nomi
            $table->string('degree')->nullable()->after('specialty');            // Turi (PhD/DSc)
            $table->string('council_number')->nullable()->after('degree');       // Ilmiy kengash raqami
            $table->string('defense_institution')->nullable()->after('council_number');   // Dissertatsiya himoya muassasi
            $table->string('performed_institution')->nullable()->after('defense_institution'); // Dissertatsiya bajarilgan muassasi
            $table->string('advisor', 500)->after('performed_institution');      // Ilmiy rahbar (required)
            $table->string('udc')->nullable()->after('advisor');                 // UO'K
            $table->string('registration_number')->nullable()->after('udc');     // Ro'yxat raqami
            $table->string('condition')->nullable()->after('registration_number'); // Holati (CopyCondition)
            $table->foreignId('publication_place_id')->nullable()->after('condition')
                ->constrained('publication_places')->nullOnDelete();             // Nashr joyi
            $table->unsignedSmallInteger('publication_year')->nullable()->after('publication_place_id'); // Nashr yili
            $table->string('inventory_number')->nullable()->after('publication_year'); // Inventari
        });
    }

    public function down(): void
    {
        Schema::table('avtoreferats', function (Blueprint $table) {
            $table->dropForeign(['publication_place_id']);
            $table->dropColumn([
                'specialty', 'degree', 'council_number', 'defense_institution',
                'performed_institution', 'advisor', 'udc', 'registration_number',
                'condition', 'publication_place_id', 'publication_year', 'inventory_number',
            ]);

            $table->foreignId('journal_issue_id')->constrained('journal_issues')->cascadeOnDelete();
        });
    }
};
