<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Loan.issued_condition/returned_condition were kept single-value when
 * "holati" (condition) went multi-select everywhere else (see
 * 2026_07_25_171501_convert_condition_columns_to_json_arrays.php) — but the
 * library wants the loan's condition snapshot to match the copy's own
 * condition, which can carry multiple tags at once. Converts both loan
 * columns the same way (string -> JSON array), same chunked-rewrite approach.
 */
return new class extends Migration
{
    private const COLUMNS = ['issued_condition', 'returned_condition'];

    public function up(): void
    {
        foreach (self::COLUMNS as $column) {
            Schema::table('loans', function (Blueprint $blueprint) use ($column): void {
                $blueprint->text($column.'_new')->nullable()->after($column);
            });

            DB::table('loans')->whereNotNull($column)->orderBy('id')->chunkById(200, function ($rows) use ($column): void {
                foreach ($rows as $row) {
                    DB::table('loans')->where('id', $row->id)->update([
                        $column.'_new' => json_encode([$row->$column]),
                    ]);
                }
            });

            Schema::table('loans', function (Blueprint $blueprint) use ($column): void {
                $blueprint->dropColumn($column);
            });

            Schema::table('loans', function (Blueprint $blueprint) use ($column): void {
                $blueprint->renameColumn($column.'_new', $column);
            });
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $column) {
            Schema::table('loans', function (Blueprint $blueprint) use ($column): void {
                $blueprint->string($column.'_old')->nullable()->after($column);
            });

            DB::table('loans')->whereNotNull($column)->orderBy('id')->chunkById(200, function ($rows) use ($column): void {
                foreach ($rows as $row) {
                    $first = json_decode((string) $row->$column, true)[0] ?? null;
                    DB::table('loans')->where('id', $row->id)->update([$column.'_old' => $first]);
                }
            });

            Schema::table('loans', function (Blueprint $blueprint) use ($column): void {
                $blueprint->dropColumn($column);
            });

            Schema::table('loans', function (Blueprint $blueprint) use ($column): void {
                $blueprint->renameColumn($column.'_old', $column);
            });
        }
    }
};
