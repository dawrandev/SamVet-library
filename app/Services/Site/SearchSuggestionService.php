<?php

namespace App\Services\Site;

use App\Models\Audiobook;
use App\Models\Avtoreferat;
use App\Models\Book;
use App\Models\Dissertation;
use App\Models\Video;
use Illuminate\Support\Collection;

/**
 * "Ehtimol shuni nazarda tutdingiz?" — when a catalog search returns (almost)
 * nothing, suggests the closest real word already in the catalog's own
 * title/author text, via edit distance. No search engine needed — at this
 * library's real scale (low hundreds of distinct words), comparing a query
 * against the whole corpus is cheap; this only ever runs on the rare
 * zero-result path, never on every search.
 */
class SearchSuggestionService
{
    /**
     * Max edit distance for a correction to be worth suggesting — beyond
     * this the "correction" is really a different word, not a typo fix.
     */
    private const MAX_SUGGESTION_DISTANCE = 2;

    /** Corpus words shorter than this are noise (initials, stray punctuation). */
    private const MIN_WORD_LENGTH = 3;

    /**
     * @return string|null  a corrected version of $term, or null when the
     *                       term is empty, already an exact corpus word, or
     *                       nothing close enough was found
     */
    public function suggest(string $term): ?string
    {
        $words = $this->splitWords($term);

        if ($words === []) {
            return null;
        }

        $corpus = $this->corpus();
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
     * Distinct, lowercase words drawn from every catalog resource's
     * title/name + author field — the vocabulary a suggestion can draw from.
     *
     * @return array<int, string>
     */
    private function corpus(): array
    {
        $texts = Collection::make()
            ->concat(Book::query()->pluck('title'))
            ->concat(Book::query()->pluck('authors'))
            ->concat(Audiobook::query()->pluck('name'))
            ->concat(Audiobook::query()->pluck('author'))
            ->concat(Video::query()->pluck('name'))
            ->concat(Video::query()->pluck('author'))
            ->concat(Dissertation::query()->pluck('title'))
            ->concat(Dissertation::query()->pluck('author'))
            ->concat(Avtoreferat::query()->pluck('title'))
            ->concat(Avtoreferat::query()->pluck('author'))
            ->filter()
            ->implode(' ');

        $words = array_filter(
            $this->splitWords($texts),
            fn (string $word): bool => mb_strlen($word) >= self::MIN_WORD_LENGTH
        );

        return array_values(array_unique($words));
    }

    /**
     * @param  array<int, string>  $corpus
     */
    private function closestWord(string $word, array $corpus): ?string
    {
        $best = null;
        $bestDistance = self::MAX_SUGGESTION_DISTANCE + 1;

        foreach ($corpus as $candidate) {
            if ($candidate === $word) {
                return null; // already a real word — nothing to correct
            }

            $distance = $this->editDistance($word, $candidate);

            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $best = $candidate;
            }
        }

        return $bestDistance <= self::MAX_SUGGESTION_DISTANCE ? $best : null;
    }

    /**
     * @return array<int, string>
     */
    private function splitWords(string $text): array
    {
        return preg_split('/\s+/u', mb_strtolower(trim($text)), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * Multi-byte-safe Levenshtein distance (Wagner–Fischer). PHP's built-in
     * levenshtein() operates on raw bytes, not UTF-8 characters — it would
     * over-count every Cyrillic (Russian) character and Uzbek-specific
     * letters (o‘, g‘) as 2-3 "characters" instead of 1, making the distance
     * threshold above meaningless for anything but plain ASCII.
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
