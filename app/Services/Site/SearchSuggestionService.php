<?php

namespace App\Services\Site;

use App\Services\SearchIndexService;
use App\Support\SearchNormalizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * "Ehtimol shuni nazarda tutdingiz?" — when a catalog search returns nothing
 * (or so little it looks like a miss), suggests the closest word that really
 * is in the catalog, via edit distance.
 *
 * Works entirely in normalized space (see SearchNormalizer): the vocabulary
 * is drawn from the same `search_text` columns the search itself matches
 * against, and the query is normalized before comparison. Two consequences
 * worth stating:
 *
 *  - A suggestion can only ever be a word that is genuinely findable — the
 *    corpus and the search index are the same text, so a correction can't
 *    point at a word that would then return nothing.
 *  - Typos are corrected across scripts too. "ветеренария" normalizes to
 *    "veterenariya", lands one edit away from the indexed "veterinariya",
 *    and is corrected — even though no Cyrillic ever reaches this class.
 *
 * This also absorbs the residual folding gaps SearchNormalizer deliberately
 * leaves alone (ц as "ts" vs "s", Uzbek х vs Russian х, word-initial е vs
 * ye): they are all distance-1 differences, so they land here rather than
 * costing precision on every single search.
 */
class SearchSuggestionService
{
    /**
     * How long the vocabulary stays cached. Only suggestions read it, so a
     * newly catalogued book being absent from *corrections* for a few minutes
     * is harmless — it is fully searchable the moment it's saved either way.
     */
    private const CORPUS_TTL_MINUTES = 30;

    private const CORPUS_CACHE_KEY = 'catalog.search_corpus';

    /** Corpus words shorter than this are noise (initials, stray fragments). */
    private const MIN_WORD_LENGTH = 3;

    /**
     * Edit distance allowed, by word length. Two edits on a four-letter word
     * is not a typo fix — it's a different word — so short words get a
     * tighter budget than long ones.
     */
    private const SHORT_WORD_LENGTH = 5;

    private const MAX_DISTANCE_SHORT = 1;

    private const MAX_DISTANCE_LONG = 2;

    /**
     * @return string|null  a corrected, normalized version of $term, or null
     *                       when the term is empty, already spelled the way
     *                       the catalog spells it, or nothing close enough
     *                       was found
     */
    public function suggest(string $term): ?string
    {
        $words = SearchNormalizer::words($term);

        if ($words === []) {
            return null;
        }

        $corpus = $this->corpus();

        if ($corpus === []) {
            return null;
        }

        $corrected = [];
        $changedAny = false;

        foreach ($words as $word) {
            $closest = $this->closestWord($word, $corpus);

            if ($closest === null) {
                $corrected[] = $word;

                continue;
            }

            $corrected[] = $closest;
            $changedAny = true;
        }

        return $changedAny ? implode(' ', $corrected) : null;
    }

    /**
     * Distinct normalized words across every PUBLIC resource's `search_text`
     * — i.e. exactly the vocabulary the public search itself can match.
     *
     * The model list comes from SearchIndexService::publicModels(), which
     * excludes Reader on purpose: this vocabulary is reachable from an
     * unauthenticated endpoint, and borrower names must never be probeable
     * through spelling suggestions.
     *
     * @return array<int, string>
     */
    private function corpus(): array
    {
        return Cache::remember(
            self::CORPUS_CACHE_KEY,
            now()->addMinutes(self::CORPUS_TTL_MINUTES),
            function (): array {
                $texts = Collection::make();

                foreach (SearchIndexService::publicModels() as $modelClass) {
                    $texts = $texts->concat($modelClass::query()->pluck('search_text'));
                }

                $words = [];

                foreach ($texts->filter() as $text) {
                    foreach (explode(' ', (string) $text) as $word) {
                        if (mb_strlen($word) >= self::MIN_WORD_LENGTH) {
                            $words[$word] = true;
                        }
                    }
                }

                return array_keys($words);
            }
        );
    }

    /**
     * @param  array<int, string>  $corpus
     */
    private function closestWord(string $word, array $corpus): ?string
    {
        $maxDistance = mb_strlen($word) <= self::SHORT_WORD_LENGTH
            ? self::MAX_DISTANCE_SHORT
            : self::MAX_DISTANCE_LONG;

        $best = null;
        $bestDistance = $maxDistance + 1;

        foreach ($corpus as $candidate) {
            if ($candidate === $word) {
                return null; // already a real word — nothing to correct
            }

            // Cheap length prefilter: two strings whose lengths differ by more
            // than the budget can't possibly be within it, and this skips the
            // O(n*m) matrix for the vast majority of the corpus.
            if (abs(mb_strlen($candidate) - mb_strlen($word)) > $maxDistance) {
                continue;
            }

            $distance = $this->editDistance($word, $candidate);

            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $best = $candidate;
            }
        }

        return $bestDistance <= $maxDistance ? $best : null;
    }

    /**
     * Multi-byte-safe Levenshtein distance (Wagner–Fischer). PHP's built-in
     * levenshtein() operates on raw bytes, not UTF-8 characters. Normalized
     * input is plain ASCII today, so that would in fact be safe here — this
     * stays character-based anyway so the class keeps working unchanged if
     * the normalizer ever starts emitting non-ASCII.
     */
    private function editDistance(string $a, string $b): int
    {
        $aChars = mb_str_split($a);
        $bChars = mb_str_split($b);
        $aLen = count($aChars);
        $bLen = count($bChars);

        if ($aLen === 0) {
            return $bLen;
        }

        if ($bLen === 0) {
            return $aLen;
        }

        $previousRow = range(0, $bLen);

        for ($i = 1; $i <= $aLen; $i++) {
            $currentRow = [$i];

            for ($j = 1; $j <= $bLen; $j++) {
                $cost = $aChars[$i - 1] === $bChars[$j - 1] ? 0 : 1;
                $currentRow[$j] = min(
                    $previousRow[$j] + 1,       // deletion
                    $currentRow[$j - 1] + 1,    // insertion
                    $previousRow[$j - 1] + $cost, // substitution
                );
            }

            $previousRow = $currentRow;
        }

        return $previousRow[$bLen];
    }
}
