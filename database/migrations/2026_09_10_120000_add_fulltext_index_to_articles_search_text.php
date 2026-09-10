<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gives `articles.search_text` the FULLTEXT index the public catalog needs.
 *
 * When the normalized search columns were added (2026_08_15_110000), articles
 * were deliberately left without this index, and that migration says why:
 * articles were only ever read through a leading-wildcard LIKE, which no index
 * can serve. That reasoning held exactly as long as articles stayed out of the
 * catalog.
 *
 * They are in it now, and CatalogRepository::applySmartSearch() puts
 * MATCH(search_text) AGAINST(...) in every default-scope query. Without the
 * index that is not a slow query, it is a hard failure — MySQL answers
 * "Can't find FULLTEXT index matching the column list" and the whole catalog
 * page and typeahead 500 on any search.
 */
return new class extends Migration
{
    private const INDEX = 'articles_search_text_fulltext';

    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->fullText('search_text', self::INDEX);
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->dropFullText(self::INDEX);
        });
    }
};
