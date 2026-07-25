<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Holati" (condition) becomes multi-select — a copy/record can carry
 * several condition tags at once (e.g. eski + betlari to'liq emas), not
 * just one. Converts the plain string column to a JSON array on the 4
 * "live condition" tables. Loan's issued_condition/returned_condition stay
 * single-value snapshots (a point-in-time record of what was true at
 * issue/return), untouched here.
 */
return new class extends Migration
{
    private const TABLES = ['book_copies', 'journal_copies', 'dissertations', 'avtoreferats'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $blueprint->text('condition_new')->nullable()->after('condition');
            });

            // Chunked DB::table (not Eloquent) — avoids the old string-cast
            // during this transitional state, same discipline as the earlier
            // "convert_book_authors_to_plain_text" migration.
            DB::table($table)->whereNotNull('condition')->orderBy('id')->chunkById(200, function ($rows) use ($table): void {
                foreach ($rows as $row) {
                    DB::table($table)->where('id', $row->id)->update([
                        'condition_new' => json_encode([$row->condition]),
                    ]);
                }
            });

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropColumn('condition');
            });

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->renameColumn('condition_new', 'condition');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $blueprint->string('condition_old')->nullable()->after('condition');
            });

            DB::table($table)->whereNotNull('condition')->orderBy('id')->chunkById(200, function ($rows) use ($table): void {
                foreach ($rows as $row) {
                    $first = json_decode((string) $row->condition, true)[0] ?? null;
                    DB::table($table)->where('id', $row->id)->update(['condition_old' => $first]);
                }
            });

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropColumn('condition');
            });

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->renameColumn('condition_old', 'condition');
            });
        }
    }
};
