<?php

namespace App\Services;

use App\Enums\BookFormat;
use App\Models\Category;
use App\Models\Language;
use Illuminate\Support\Facades\DB;

/**
 * Per-year breakdown of book copies received (kirish akti sanasi /
 * acquisition_act_at), sliced 3 ways — category, format, language — each
 * with both copy (nusxa) and title (nomi) counts. Shared by the admin
 * dashboard (DashboardService) and the public statistics page
 * (StatisticsService) — both show the exact same report, just styled
 * differently, so the aggregation itself lives in one place.
 */
class AnnualAcquisitionReportService
{
    /**
     * Top-level categories plus a closure resolving any category id to
     * itself + every descendant id. Exposed publicly so a caller that also
     * needs the plain (non-year-sliced) category rollup — e.g.
     * DashboardService::literatureByCategory() — can compute it once and
     * reuse it here too, instead of paying for the underlying per-category
     * pivot query twice in the same request.
     *
     * @return array{0: \Illuminate\Support\Collection<int, Category>, 1: \Closure(int): array<int, int>}
     */
    public function categoryRollupIds(): array
    {
        $topCategories = Category::query()->whereNull('parent_id')->orderBy('sort_order')->orderBy('id')->get(['id', 'name']);
        $childrenByParent = Category::query()->whereNotNull('parent_id')->get(['id', 'parent_id'])->groupBy('parent_id');

        $descendantIds = function (int $id) use (&$descendantIds, $childrenByParent): array {
            $ids = [$id];
            foreach ($childrenByParent->get($id, collect()) as $child) {
                array_push($ids, ...$descendantIds($child->id));
            }

            return $ids;
        };

        return [$topCategories, $descendantIds];
    }

    /**
     * Dissertation/Avtoreferat have their own acquisition_act_at column on
     * their own table, never on BookCopy, so querying BookCopy here is
     * inherently book-only — no extra filtering needed to exclude them.
     *
     * @param  array{0: \Illuminate\Support\Collection<int, Category>, 1: \Closure(int): array<int, int>}|null  $categoryRollup  pass a pre-computed rollup to avoid a redundant query when the caller already has one
     * @return array{years: array<int, int>, category: array{labels: array<int, string>, copies: array<int, array<int, int>>, titles: array<int, array<int, int>>, otherIndex: ?int}, format: array{labels: array<int, string>, copies: array<int, array<int, int>>, titles: array<int, array<int, int>>, otherIndex: ?int}, language: array{labels: array<int, string>, copies: array<int, array<int, int>>, titles: array<int, array<int, int>>, otherIndex: ?int}}
     */
    public function report(?array $categoryRollup = null): array
    {
        $categoryRollup ??= $this->categoryRollupIds();

        $empty = ['labels' => [], 'copies' => [], 'titles' => [], 'otherIndex' => null];

        // Plain query builder, not BookCopy::query() — BookCopy casts
        // 'format' to a BookFormat enum on hydration, which would turn
        // $row->format below into an enum object instead of the raw string
        // the format-index lookup expects. DB::table() never hydrates a
        // model, so every column comes back as a plain scalar.
        $range = DB::table('book_copies')
            ->whereNotNull('acquisition_act_at')
            ->selectRaw('MIN(YEAR(acquisition_act_at)) as min_year, MAX(YEAR(acquisition_act_at)) as max_year')
            ->first();

        if ($range === null || $range->min_year === null) {
            return ['years' => [], 'category' => $empty, 'format' => $empty, 'language' => $empty];
        }

        $years = range((int) $range->min_year, (int) $range->max_year);
        $yearIndex = array_flip($years);

        $rows = DB::table('book_copies')
            ->join('books', 'books.id', '=', 'book_copies.book_id')
            ->whereNotNull('book_copies.acquisition_act_at')
            ->selectRaw('book_copies.book_id, book_copies.format, books.language_id, YEAR(book_copies.acquisition_act_at) as year')
            ->get();

        return [
            'years' => $years,
            'category' => $this->annualByCategory($rows, $years, $yearIndex, $categoryRollup),
            'format' => $this->annualByFormat($rows, $years, $yearIndex),
            'language' => $this->annualByLanguage($rows, $years, $yearIndex),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \stdClass>  $rows
     * @param  array<int, int>  $years
     * @param  array<int, int>  $yearIndex  year => index into $years
     * @return array{labels: array<int, string>, copies: array<int, array<int, int>>, titles: array<int, array<int, int>>}
     */
    private function annualByFormat($rows, array $years, array $yearIndex): array
    {
        $cases = BookFormat::cases();
        $labels = array_map(fn (BookFormat $f) => $f->label(), $cases);
        $formatIndex = array_flip(array_map(fn (BookFormat $f) => $f->value, $cases));

        $copies = array_fill(0, count($cases), array_fill(0, count($years), 0));
        $titleSeen = [];

        foreach ($rows as $row) {
            $fmtIdx = $formatIndex[$row->format] ?? null;
            $yIdx = $yearIndex[(int) $row->year] ?? null;
            if ($fmtIdx === null || $yIdx === null) {
                continue;
            }

            $copies[$fmtIdx][$yIdx]++;
            $titleSeen["{$fmtIdx}:{$yIdx}"][$row->book_id] = true;
        }

        $titles = [];
        foreach ($labels as $i => $label) {
            $titles[$i] = [];
            foreach (array_keys($years) as $yi) {
                $titles[$i][$yi] = count($titleSeen["{$i}:{$yi}"] ?? []);
            }
        }

        return ['labels' => $labels, 'copies' => $copies, 'titles' => $titles, 'otherIndex' => null];
    }

    /**
     * A book can belong to more than one top-level category — each matching
     * segment is incremented independently (same accepted convention as
     * DashboardService::literatureByCategory(), not a bug). A book with zero
     * categories is folded into a "Boshqa" segment instead of silently
     * vanishing from this dimension's stacked total (it's still present in
     * format/language). The overflow segment's position is returned as
     * `otherIndex` — the caller must key its color off that index, not off
     * the "Boshqa" label text, since a real top-level category could itself
     * be named "Boshqa".
     *
     * @param  \Illuminate\Support\Collection<int, \stdClass>  $rows
     * @param  array<int, int>  $years
     * @param  array<int, int>  $yearIndex
     * @param  array{0: \Illuminate\Support\Collection<int, Category>, 1: \Closure(int): array<int, int>}  $categoryRollup
     * @return array{labels: array<int, string>, copies: array<int, array<int, int>>, titles: array<int, array<int, int>>, otherIndex: int}
     */
    private function annualByCategory($rows, array $years, array $yearIndex, array $categoryRollup): array
    {
        [$topCategories, $descendantIds] = $categoryRollup;

        $labels = $topCategories->pluck('name')->values()->all();
        $bookTopIndexes = [];

        foreach ($topCategories->values() as $i => $category) {
            $bookIds = DB::table('book_category')
                ->whereIn('category_id', $descendantIds($category->id))
                ->pluck('book_id')
                ->unique();

            foreach ($bookIds as $bookId) {
                $bookTopIndexes[$bookId][] = $i;
            }
        }

        $otherIdx = count($labels);
        $labels[] = __('Boshqa');

        $copies = array_fill(0, $otherIdx + 1, array_fill(0, count($years), 0));
        $titleSeen = [];

        foreach ($rows as $row) {
            $yIdx = $yearIndex[(int) $row->year] ?? null;
            if ($yIdx === null) {
                continue;
            }

            $segments = $bookTopIndexes[$row->book_id] ?? [$otherIdx];

            foreach ($segments as $segIdx) {
                $copies[$segIdx][$yIdx]++;
                $titleSeen["{$segIdx}:{$yIdx}"][$row->book_id] = true;
            }
        }

        $titles = [];
        foreach ($labels as $i => $label) {
            $titles[$i] = [];
            foreach (array_keys($years) as $yi) {
                $titles[$i][$yi] = count($titleSeen["{$i}:{$yi}"] ?? []);
            }
        }

        return ['labels' => $labels, 'copies' => $copies, 'titles' => $titles, 'otherIndex' => $otherIdx];
    }

    /**
     * Named languages are ranked by their total across all years and only
     * the top 6 (matching the existing non-year-sliced language chart's
     * palette size) keep a distinct segment; the rest, plus books with no
     * language at all, are folded into a single "Boshqa" segment — never
     * two, so labels stay unique regardless of how many languages exist.
     * The overflow segment's position (or null if every language fit) is
     * returned as `otherIndex` — the caller must key its color off that
     * index, not off the "Boshqa" label text, since a real language could
     * itself be named "Boshqa".
     *
     * @param  \Illuminate\Support\Collection<int, \stdClass>  $rows
     * @param  array<int, int>  $years
     * @param  array<int, int>  $yearIndex
     * @return array{labels: array<int, string>, copies: array<int, array<int, int>>, titles: array<int, array<int, int>>, otherIndex: ?int}
     */
    private function annualByLanguage($rows, array $years, array $yearIndex): array
    {
        $yearCount = count($years);
        $languageNames = Language::query()->pluck('name', 'id');

        $perLangCopies = [];
        $perLangTitleSeen = [];

        foreach ($rows as $row) {
            $yIdx = $yearIndex[(int) $row->year] ?? null;
            if ($yIdx === null) {
                continue;
            }

            $key = $row->language_id ?? 'other';
            $perLangCopies[$key][$yIdx] = ($perLangCopies[$key][$yIdx] ?? 0) + 1;
            $perLangTitleSeen[$key][$yIdx][$row->book_id] = true;
        }

        $namedKeys = array_values(array_filter(array_keys($perLangCopies), fn ($k) => $k !== 'other'));
        $totals = [];
        foreach ($namedKeys as $key) {
            $totals[$key] = array_sum($perLangCopies[$key]);
        }
        arsort($totals);

        $topKeys = array_slice(array_keys($totals), 0, 6);
        $overflowKeys = array_values(array_diff($namedKeys, $topKeys));
        if (isset($perLangCopies['other'])) {
            $overflowKeys[] = 'other';
        }

        $labels = $copies = $titles = [];
        $otherIndex = null;

        foreach ($topKeys as $key) {
            $labels[] = $languageNames[$key] ?? '—';
            $copies[] = array_map(fn ($yi) => $perLangCopies[$key][$yi] ?? 0, range(0, $yearCount - 1));
            $titles[] = array_map(fn ($yi) => count($perLangTitleSeen[$key][$yi] ?? []), range(0, $yearCount - 1));
        }

        if (! empty($overflowKeys)) {
            $restCopies = array_fill(0, $yearCount, 0);
            $restTitleSeen = array_fill(0, $yearCount, []);

            foreach ($overflowKeys as $key) {
                foreach ($perLangCopies[$key] ?? [] as $yi => $c) {
                    $restCopies[$yi] += $c;
                }
                foreach ($perLangTitleSeen[$key] ?? [] as $yi => $bookIds) {
                    foreach (array_keys($bookIds) as $bookId) {
                        $restTitleSeen[$yi][$bookId] = true;
                    }
                }
            }

            $otherIndex = count($labels);
            $labels[] = __('Boshqa');
            $copies[] = $restCopies;
            $titles[] = array_map(fn ($seen) => count($seen), $restTitleSeen);
        }

        return ['labels' => $labels, 'copies' => $copies, 'titles' => $titles, 'otherIndex' => $otherIndex];
    }
}
