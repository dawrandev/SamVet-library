<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = ['books', 'journals'];

    public function up(): void
    {
        // Publisher is no longer translatable — a single plain text field.
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('publisher_plain')->nullable()->after('publisher');
            });

            DB::table($table)->select('id', 'publisher')->orderBy('id')->chunkById(200, function ($rows) use ($table) {
                foreach ($rows as $row) {
                    $decoded = json_decode((string) $row->publisher, true);
                    $value = is_array($decoded)
                        ? ($decoded['uz'] ?? collect($decoded)->first(fn ($v) => filled($v)))
                        : $row->publisher;

                    DB::table($table)->where('id', $row->id)->update(['publisher_plain' => $value ?: null]);
                }
            });

            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('publisher');
            });
            Schema::table($table, function (Blueprint $t) {
                $t->renameColumn('publisher_plain', 'publisher');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->json('publisher_json')->nullable()->after('publisher');
            });

            DB::table($table)->select('id', 'publisher')->orderBy('id')->chunkById(200, function ($rows) use ($table) {
                foreach ($rows as $row) {
                    DB::table($table)->where('id', $row->id)->update([
                        'publisher_json' => $row->publisher !== null ? json_encode(['uz' => $row->publisher]) : null,
                    ]);
                }
            });

            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('publisher');
            });
            Schema::table($table, function (Blueprint $t) {
                $t->renameColumn('publisher_json', 'publisher');
            });
        }
    }
};
