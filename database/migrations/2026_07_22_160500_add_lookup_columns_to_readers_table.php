<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('readers', function (Blueprint $table) {
            $table->foreignId('affiliation_place_id')->nullable()->after('affiliation_place')->constrained('affiliation_places')->nullOnDelete();
            $table->foreignId('affiliation_unit_id')->nullable()->after('affiliation_unit')->constrained('affiliation_units')->nullOnDelete();
            $table->foreignId('affiliation_group_id')->nullable()->after('affiliation_group')->constrained('affiliation_groups')->nullOnDelete();
            $table->foreignId('region_id')->nullable()->after('district')->constrained('regions')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->after('region_id')->constrained('districts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('readers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('affiliation_place_id');
            $table->dropConstrainedForeignId('affiliation_unit_id');
            $table->dropConstrainedForeignId('affiliation_group_id');
            $table->dropConstrainedForeignId('region_id');
            $table->dropConstrainedForeignId('district_id');
        });
    }
};
