<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The "unit + necha marta" pair is replaced by a single named-frequency
     * value (App\Enums\JournalPeriodicity, now with more cases — see the
     * enum's own docblock). Existing weekly+2 rows ("2 marta haftada") map
     * exactly onto the new "semiweekly" case; every other combination
     * (count = 1 or null) just drops the count, keeping its own unit as-is,
     * since those values are unchanged case names in the new enum.
     */
    public function up(): void
    {
        DB::table('journals')
            ->where('periodicity', 'weekly')
            ->where('periodicity_count', 2)
            ->update(['periodicity' => 'semiweekly']);

        Schema::table('journals', function (Blueprint $table) {
            $table->dropColumn('periodicity_count');
        });
    }

    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->unsignedTinyInteger('periodicity_count')->nullable()->after('periodicity');
        });

        DB::table('journals')
            ->where('periodicity', 'semiweekly')
            ->update(['periodicity' => 'weekly', 'periodicity_count' => 2]);
    }
};
