<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('readers', function (Blueprint $table) {
            $table->dropColumn(['affiliation_place', 'affiliation_unit', 'affiliation_group', 'district']);
        });
    }

    public function down(): void
    {
        Schema::table('readers', function (Blueprint $table) {
            $table->string('affiliation_place')->nullable()->after('full_name');
            $table->string('affiliation_unit')->nullable()->after('affiliation_place');
            $table->string('affiliation_group')->nullable()->after('affiliation_unit');
            $table->string('district')->nullable()->after('pinfl');
        });
    }
};
