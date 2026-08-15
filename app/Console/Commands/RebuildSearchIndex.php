<?php

namespace App\Console\Commands;

use App\Services\SearchIndexService;
use Illuminate\Console\Command;

/**
 * Re-derives every catalog row's normalized search columns.
 *
 * Needed in exactly two situations: right after the migration that adds the
 * columns (existing rows have them empty until this runs, and an unindexed
 * row is invisible to search), and whenever SearchNormalizer's
 * transliteration table changes, since the stored text was folded with the
 * old rules.
 */
class RebuildSearchIndex extends Command
{
    protected $signature = 'search:reindex {--model= : Only one model (book, audiobook, video, dissertation, avtoreferat, article, journal, reader)}';

    protected $description = 'Rebuild the normalized (script-independent) search columns';

    public function handle(SearchIndexService $searchIndex): int
    {
        $modelOption = $this->option('model');
        $only = null;

        if ($modelOption !== null) {
            $only = SearchIndexService::resolveModel($modelOption);

            if ($only === null) {
                $this->error("Unknown model: {$modelOption}");

                return self::FAILURE;
            }
        }

        $this->info('Rebuilding search index...');

        $counts = $searchIndex->reindex($only);

        foreach ($counts as $type => $count) {
            $this->line("  {$type}: {$count}");
        }

        $this->info('Done — '.array_sum($counts).' rows reindexed.');

        return self::SUCCESS;
    }
}
