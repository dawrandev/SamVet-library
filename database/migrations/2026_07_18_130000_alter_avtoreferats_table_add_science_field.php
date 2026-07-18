<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Fan nomi" was missing from the avtoreferat mockup — same lookup Dissertation
     * already uses. `publication_year` is renamed to `defense_year` (Himoya yili):
     * for an avtoreferat this always meant the dissertation-defense year, not a
     * publication year — the old label/name was a copy-paste mismatch.
     */
    public function up(): void
    {
        Schema::table('avtoreferats', function (Blueprint $table) {
            $table->foreignId('science_field_id')->nullable()->after('specialty')
                ->constrained('science_fields')->nullOnDelete();
        });

        Schema::table('avtoreferats', function (Blueprint $table) {
            $table->renameColumn('publication_year', 'defense_year');
        });
    }

    public function down(): void
    {
        Schema::table('avtoreferats', function (Blueprint $table) {
            $table->renameColumn('defense_year', 'publication_year');
        });

        Schema::table('avtoreferats', function (Blueprint $table) {
            $table->dropForeign(['science_field_id']);
            $table->dropColumn('science_field_id');
        });
    }
};
