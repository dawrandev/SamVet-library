<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Script-independent search. Each catalog resource gains two derived columns,
 * both filled by App\Support\SearchNormalizer (via the model observers, and
 * backfilled by `search:reindex`):
 *
 *  - search_text       every free-text field of the row, transliterated to
 *                      one canonical Latin form and concatenated. This is
 *                      what the default "Barchasi" search matches against,
 *                      so a Cyrillic query reaches a Latin-script record and
 *                      vice versa.
 *  - title_normalized  the same treatment for the title/name alone, used for
 *                      the relevance boost (a title hit must outrank an
 *                      incidental annotation hit) and the "Kitob nomi" scope.
 *
 * The previous per-field composite FULLTEXT indexes are dropped: nothing
 * queries them any more now that every match runs through search_text, and
 * leaving five multi-column FULLTEXT indexes in place would cost write
 * throughput on every catalog save for no read benefit. down() restores them
 * exactly as 2026_07_30_100000 created them.
 *
 * title_normalized deliberately gets no index — it is only ever read with a
 * leading-wildcard LIKE ('%term%'), which no B-tree can serve anyway.
 */
return new class extends Migration
{
    /**
     * Tables and their old FULLTEXT column sets, in one place so up() and
     * down() can't drift apart.
     *
     * @var array<string, array<int, string>>
     */
    private const LEGACY_FULLTEXT = [
        'books' => ['title', 'authors', 'annotation', 'udc'],
        'audiobooks' => ['name', 'author', 'annotation'],
        'videos' => ['name', 'author', 'annotation'],
        'dissertations' => ['title', 'author', 'annotation'],
        'avtoreferats' => ['title', 'author', 'annotation'],
    ];

    public function up(): void
    {
        foreach (array_keys(self::LEGACY_FULLTEXT) as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $blueprint->text('search_text')->nullable()->after('id');
                $blueprint->string('title_normalized', 500)->nullable()->after('search_text');
                $blueprint->fullText('search_text', "{$table}_search_text_fulltext");
            });
        }

        foreach (self::LEGACY_FULLTEXT as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns): void {
                $blueprint->dropFullText($columns);
            });
        }
    }

    public function down(): void
    {
        foreach (self::LEGACY_FULLTEXT as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns): void {
                $blueprint->dropFullText("{$table}_search_text_fulltext");
                $blueprint->dropColumn(['search_text', 'title_normalized']);
                $blueprint->fullText($columns);
            });
        }
    }
};
