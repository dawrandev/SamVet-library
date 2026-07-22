<?php

namespace App\Services;

use App\Enums\CopyStatus;
use App\Enums\ReaderStatus;
use App\Models\Article;
use App\Models\Author;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\BookReading;
use App\Models\Category;
use App\Models\Computer;
use App\Models\Journal;
use App\Models\Language;
use App\Models\News;
use App\Models\Reader;
use App\Models\Subscription;
use Illuminate\Support\Carbon;

/**
 * Aggregated figures for the admin dashboard (the most useful librarian metrics).
 */
class DashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function stats(?string $from = null, ?string $to = null): array
    {
        // Status breakdowns in a single grouped query each (no per-status count queries).
        $copiesByStatus = BookCopy::query()->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');
        $copiesByFormat = BookCopy::query()->selectRaw('format, COUNT(*) as c')->groupBy('format')->pluck('c', 'format');
        $readersByType = Reader::query()->selectRaw('type, COUNT(*) as c')->groupBy('type')->pluck('c', 'type');

        $booksByLanguage = Book::query()
            ->whereNotNull('language_id')
            ->selectRaw('language_id, COUNT(*) as c')
            ->groupBy('language_id')
            ->pluck('c', 'language_id');
        $languageNames = Language::query()->whereIn('id', $booksByLanguage->keys())->pluck('name', 'id');

        [$rangeFrom, $rangeTo] = $this->resolveReadingRange($from, $to);

        $onlineReadings = BookReading::with(['reader', 'book'])
            ->whereBetween('read_at', [$rangeFrom, $rangeTo])
            ->latest('read_at')
            ->paginate(20, ['*'], 'readings_page')
            ->withQueryString();

        return [
            'onlineReadingsFrom' => $rangeFrom,
            'onlineReadingsTo' => $rangeTo,
            'onlineReadings' => $onlineReadings,
            // KPI
            'booksTotal' => Book::count(),
            'copiesTotal' => BookCopy::count(),
            'copiesAvailable' => (int) ($copiesByStatus[CopyStatus::Available->value] ?? 0),
            'readersTotal' => Reader::count(),
            'readersActive' => Reader::where('status', ReaderStatus::Active->value)->count(),

            // Breakdowns (value => count) — loan/overdue counts live on the
            // "Berilgan kitoblar" page itself (and the header/sidebar badge),
            // not duplicated here.
            'copiesByStatus' => $copiesByStatus,
            'copiesByFormat' => $copiesByFormat,
            'readersByType' => $readersByType,
            'booksByLanguage' => $booksByLanguage,
            'languageNames' => $languageNames,

            // Secondary counts
            'journalsTotal' => Journal::count(),
            'articlesTotal' => Article::count(),
            'newsTotal' => News::count(),
            'computersTotal' => Computer::count(),
            'subscriptionsTotal' => Subscription::count(),
            'subscriptionsAmount' => (float) Subscription::sum('amount'),
            'categoriesTotal' => Category::count(),
            'authorsTotal' => Author::count(),
        ];
    }

    /**
     * The librarian picks a from/to; defaults to "today so far" when not given.
     * Datetime-local inputs submit "Y-m-d\TH:i" — Carbon parses that natively.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveReadingRange(?string $from, ?string $to): array
    {
        try {
            $rangeFrom = $from ? Carbon::parse($from) : Carbon::today();
        } catch (\Exception) {
            $rangeFrom = Carbon::today();
        }

        try {
            $rangeTo = $to ? Carbon::parse($to) : Carbon::now();
        } catch (\Exception) {
            $rangeTo = Carbon::now();
        }

        if ($rangeFrom->gt($rangeTo)) {
            [$rangeFrom, $rangeTo] = [$rangeTo, $rangeFrom];
        }

        return [$rangeFrom, $rangeTo];
    }
}
