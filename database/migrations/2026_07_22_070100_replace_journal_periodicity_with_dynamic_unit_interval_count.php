<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A fixed list of named frequencies (daily/weekly/biweekly/...) still could
     * not express every real schedule — "2 haftada 3 marta" needs an interval
     * AND a count together, not a name picked from a closed list. Replaced with
     * a dynamic unit + interval + count triple so any "M marta N unitda"
     * schedule can be expressed.
     */
    private const MAP = [
        'daily' => ['unit' => 'day', 'interval' => 1, 'count' => 1],
        'semiweekly' => ['unit' => 'week', 'interval' => 1, 'count' => 2],
        'weekly' => ['unit' => 'week', 'interval' => 1, 'count' => 1],
        'biweekly' => ['unit' => 'week', 'interval' => 2, 'count' => 1],
        'semimonthly' => ['unit' => 'month', 'interval' => 1, 'count' => 2],
        'monthly' => ['unit' => 'month', 'interval' => 1, 'count' => 1],
        'bimonthly' => ['unit' => 'month', 'interval' => 2, 'count' => 1],
        'quarterly' => ['unit' => 'month', 'interval' => 3, 'count' => 1],
        'irregular' => ['unit' => 'irregular', 'interval' => null, 'count' => null],
    ];

    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->string('periodicity_unit')->nullable()->after('periodicity');
            $table->unsignedTinyInteger('periodicity_interval')->nullable()->after('periodicity_unit');
            $table->unsignedTinyInteger('periodicity_count')->nullable()->after('periodicity_interval');
        });

        foreach (self::MAP as $old => $new) {
            DB::table('journals')->where('periodicity', $old)->update([
                'periodicity_unit' => $new['unit'],
                'periodicity_interval' => $new['interval'],
                'periodicity_count' => $new['count'],
            ]);
        }

        Schema::table('journals', function (Blueprint $table) {
            $table->dropColumn('periodicity');
        });
    }

    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->string('periodicity')->nullable()->after('index');
        });

        $reverse = [];
        foreach (self::MAP as $old => $new) {
            $reverse[$new['unit'].':'.$new['interval'].':'.$new['count']] = $old;
        }

        DB::table('journals')
            ->select('id', 'periodicity_unit', 'periodicity_interval', 'periodicity_count')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($reverse) {
                foreach ($rows as $row) {
                    $key = $row->periodicity_unit.':'.$row->periodicity_interval.':'.$row->periodicity_count;
                    if (isset($reverse[$key])) {
                        DB::table('journals')->where('id', $row->id)->update(['periodicity' => $reverse[$key]]);
                    }
                }
            });

        Schema::table('journals', function (Blueprint $table) {
            $table->dropColumn(['periodicity_unit', 'periodicity_interval', 'periodicity_count']);
        });
    }
};
