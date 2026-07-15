<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fixed 2-value newspaper type (NewspaperType enum) — separate from the
        // open journal_type_id lookup, which journals keep using unchanged.
        Schema::table('journals', function (Blueprint $table) {
            $table->string('newspaper_type')->nullable()->after('journal_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->dropColumn('newspaper_type');
        });
    }
};
