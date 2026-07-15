<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "Yillik"/"Yarim yillik" are dropped from the periodicity enum (real
        // publications here never run that infrequently) — clear any existing
        // rows using those values so they don't break the app-level Enum cast.
        DB::table('journals')->whereIn('periodicity', ['annual', 'semiannual'])->update(['periodicity' => null]);

        Schema::table('journals', function (Blueprint $table) {
            $table->unsignedTinyInteger('periodicity_count')->nullable()->after('periodicity');
        });
    }

    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->dropColumn('periodicity_count');
        });
    }
};
