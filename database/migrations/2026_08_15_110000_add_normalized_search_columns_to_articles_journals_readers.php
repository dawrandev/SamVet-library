<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extends script-independent search (2026_08_15_100000) past the five catalog
 * resource types to the three remaining tables where a human name or title is
 * typed into a search box:
 *
 *  - articles / journals — public periodical content, searched in admin today
 *    and now also contributing to the public "did you mean" vocabulary.
 *  - readers — librarian-only. A borrower's name is exactly the kind of text
 *    that gets written in either script ("Ўлмасов" vs "Oʻlmasov"), so the
 *    same folding applies. Reader words are deliberately kept OUT of the
 *    public suggestion corpus (see SearchIndexService::PUBLIC_MODELS) — that
 *    endpoint is unauthenticated and must never leak borrower names.
 *
 * Columns are filled by SearchIndexService via each model's observer, and
 * backfilled by `php artisan search:reindex`.
 *
 * No FULLTEXT index here: these three are only ever read with a
 * leading-wildcard LIKE ('%term%'), which no index can serve. The catalog's
 * own search_text keeps its FULLTEXT because MATCH() participates there.
 */
return new class extends Migration
{
    /** @var array<int, string> */
    private const TABLES = ['articles', 'journals', 'readers'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->text('search_text')->nullable()->after('id');
                $blueprint->string('title_normalized', 500)->nullable()->after('search_text');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropColumn(['search_text', 'title_normalized']);
            });
        }
    }
};
