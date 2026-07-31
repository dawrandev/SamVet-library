<?php

namespace App\Services;

use App\Enums\SubscriptionSource;
use App\Models\Article;
use App\Models\Audiobook;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\BookReading;
use App\Models\Category;
use App\Models\Computer;
use App\Models\ComputerSession;
use App\Models\EventParticipant;
use App\Models\Journal;
use App\Models\Language;
use App\Models\Loan;
use App\Models\News;
use App\Models\Reader;
use App\Models\ReaderType;
use App\Models\Subscription;
use App\Models\Video;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Aggregated figures for the admin dashboard (the most useful librarian metrics).
 */
class DashboardService
{
    public function __construct(
        private readonly ReaderStatsService $readerStats,
        private readonly AnnualAcquisitionReportService $annualAcquisitionReportService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function stats(?string $from = null, ?string $to = null, ?string $statsFrom = null, ?string $statsTo = null): array
    {
        // Computed once and shared — literatureByCategory() and
        // AnnualAcquisitionReportService::report() both roll up the same
        // top-level categories, and this rollup already costs one query per
        // top category, so it shouldn't run twice in the same request.
        $categoryRollup = $this->annualAcquisitionReportService->categoryRollupIds();

        // Status breakdowns in a single grouped query each (no per-status count queries).
        $copiesByStatus = BookCopy::query()->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

        // Bosma/brayl are real physical inventory — a BookCopy row per unit,
        // so counting rows is correct stock. "Elektron" isn't stock in that
        // sense (BookFormat's own docblock: "the online-reading PDF is
        // stored separately in books.electronic_file") — a book is
        // digitized once, not "N electronic copies", so it's counted as
        // distinct books, via either signal (a cataloged electronic
        // BookCopy row, or a real online-readable PDF file).
        $copiesByFormat = collect([
            'print' => BookCopy::where('format', 'print')->count(),
            'braille' => BookCopy::where('format', 'braille')->count(),
            'electronic' => Book::where(
                fn ($q) => $q->whereNotNull('electronic_file')->orWhereHas('copies', fn ($q2) => $q2->where('format', 'electronic'))
            )->count(),
        ]);

        // "By title" (nomi) — bosma/brayl counted by distinct book, not copy
        // row; elektron is already a title count under $copiesByFormat (see
        // the comment above), so it's reused as-is for both modes.
        $titlesByFormat = collect([
            'print' => Book::whereHas('copies', fn ($q) => $q->where('format', 'print'))->count(),
            'braille' => Book::whereHas('copies', fn ($q) => $q->where('format', 'braille'))->count(),
            'electronic' => $copiesByFormat['electronic'],
        ]);

        $readersByType = Reader::query()->selectRaw('reader_type_id, COUNT(*) as c')->groupBy('reader_type_id')->pluck('c', 'reader_type_id');

        $booksByLanguage = Book::query()
            ->whereNotNull('language_id')
            ->selectRaw('language_id, COUNT(*) as c')
            ->groupBy('language_id')
            ->pluck('c', 'language_id');

        // "By copy" (nusxa) — BookCopy has no language of its own, so it's
        // counted through its parent Book's language.
        $copiesByLanguage = BookCopy::query()
            ->join('books', 'books.id', '=', 'book_copies.book_id')
            ->whereNotNull('books.language_id')
            ->selectRaw('books.language_id as language_id, COUNT(*) as c')
            ->groupBy('books.language_id')
            ->pluck('c', 'language_id');

        $languageNames = Language::query()
            ->whereIn('id', $booksByLanguage->keys()->merge($copiesByLanguage->keys())->unique())
            ->pluck('name', 'id');

        [$rangeFrom, $rangeTo] = $this->resolveReadingRange($from, $to);

        [$statsRangeFrom, $statsRangeTo] = $this->resolveStatsRange($statsFrom, $statsTo);
        $daily = $this->dailyUsage($statsRangeFrom, $statsRangeTo);

        $subscriptionYear = (int) Carbon::now()->year;
        $subscribersThisYear = Subscription::query()
            ->where('year', $subscriptionYear)
            ->where('source', SubscriptionSource::Reader->value)
            ->distinct('reader_id')
            ->count('reader_id');

        $onlineReadings = BookReading::with(['reader', 'book'])
            ->whereBetween('read_at', [$rangeFrom, $rangeTo])
            ->latest('read_at')
            ->paginate(20, ['*'], 'readings_page')
            ->withQueryString();

        return [
            'onlineReadingsFrom' => $rangeFrom,
            'onlineReadingsTo' => $rangeTo,
            'onlineReadings' => $onlineReadings,
            // KPI — book totals feed the "Kitob nomi" donut (nomda vs nusxada).
            'booksTotal' => Book::count(),
            'copiesTotal' => BookCopy::count(),

            // Breakdowns (value => count) — loan/overdue counts live on the
            // "Berilgan kitoblar" page itself (and the header/sidebar badge),
            // not duplicated here.
            'copiesByStatus' => $copiesByStatus,
            'copiesByFormat' => $copiesByFormat,
            'audiobooksTotal' => Audiobook::count(),
            'videosTotal' => Video::count(),
            'readersByType' => $readersByType,
            'readerTypes' => ReaderType::query()->orderBy('id')->get(),
            'booksByLanguage' => $booksByLanguage,
            'copiesByLanguage' => $copiesByLanguage,
            'languageNames' => $languageNames,

            // Daily usage line chart
            'statsFrom' => $statsRangeFrom,
            'statsTo' => $statsRangeTo,
            'dailyUsage' => $daily,

            // Secondary counts
            'journalsTotal' => Journal::count(),
            'articlesTotal' => Article::count(),
            'newsTotal' => News::count(),
            'computersTotal' => Computer::count(),
            'subscriptionYear' => $subscriptionYear,
            'subscribersThisYear' => $subscribersThisYear,
            'categoriesTotal' => Category::count(),

            // Reader demographics
            'readersByGender' => $this->readerStats->byGender(),
            'readersByNationality' => $this->readersByNationality(),
            'readersByAgeGroup' => $this->readerStats->byAgeGroup(),

            // Fund composition, nomi/nusxa toggles
            'titlesByFormat' => $titlesByFormat,
            'categoryStats' => $this->literatureByCategory($categoryRollup),

            // Annual acquisition report (Yillik hisobot) — by category/format/language
            'annualReport' => $this->annualAcquisitionReportService->report($categoryRollup),

            // Annual expenses report (Yillik xarajatlar hisoboti) — books vs. periodicals
            'annualExpenses' => $this->annualExpensesReport(),
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

    /**
     * The librarian picks a from/to date; defaults to the last 7 days (weekly).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveStatsRange(?string $from, ?string $to): array
    {
        try {
            $rangeTo = $to ? Carbon::parse($to)->endOfDay() : Carbon::today()->endOfDay();
        } catch (\Exception) {
            $rangeTo = Carbon::today()->endOfDay();
        }

        try {
            $rangeFrom = $from ? Carbon::parse($from)->startOfDay() : $rangeTo->copy()->subDays(6)->startOfDay();
        } catch (\Exception) {
            $rangeFrom = $rangeTo->copy()->subDays(6)->startOfDay();
        }

        if ($rangeFrom->gt($rangeTo)) {
            [$rangeFrom, $rangeTo] = [$rangeTo, $rangeFrom];
        }

        return [$rangeFrom, $rangeTo];
    }

    /**
     * Day-bucketed counts for the "Kunlik statistika" line chart: books
     * issued, books returned, online reads, computer sessions, event
     * participations, and their daily sum — every day in the range present,
     * zero-filled. Returns are excluded from the "total" sum — a return is a
     * librarian-side closing action, not reader-initiated usage.
     *
     * @return array{dates: array<int, string>, loans: array<int, int>, returns: array<int, int>, onlineReadings: array<int, int>, computerSessions: array<int, int>, eventParticipations: array<int, int>, total: array<int, int>}
     */
    private function dailyUsage(Carbon $from, Carbon $to): array
    {
        $dates = [];
        for ($cursor = $from->copy()->startOfDay(); $cursor->lte($to); $cursor->addDay()) {
            $dates[] = $cursor->format('Y-m-d');
        }

        $loans = Loan::query()
            ->whereBetween('issued_at', [$from, $to])
            ->selectRaw('DATE(issued_at) as d, COUNT(*) as c')
            ->groupBy('d')->pluck('c', 'd');

        $returns = Loan::query()
            ->whereBetween('returned_at', [$from, $to])
            ->selectRaw('DATE(returned_at) as d, COUNT(*) as c')
            ->groupBy('d')->pluck('c', 'd');

        $readings = BookReading::query()
            ->whereBetween('read_at', [$from, $to])
            ->selectRaw('DATE(read_at) as d, COUNT(*) as c')
            ->groupBy('d')->pluck('c', 'd');

        $sessions = ComputerSession::query()
            ->whereBetween('issued_at', [$from, $to])
            ->selectRaw('DATE(issued_at) as d, COUNT(*) as c')
            ->groupBy('d')->pluck('c', 'd');

        $participations = EventParticipant::query()
            ->join('events', 'events.id', '=', 'event_participants.event_id')
            ->whereBetween('events.date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('events.date as d, COUNT(*) as c')
            ->groupBy('d')->pluck('c', 'd');

        $series = ['loans' => [], 'returns' => [], 'onlineReadings' => [], 'computerSessions' => [], 'eventParticipations' => [], 'total' => []];

        foreach ($dates as $d) {
            $l = (int) ($loans[$d] ?? 0);
            $ret = (int) ($returns[$d] ?? 0);
            $r = (int) ($readings[$d] ?? 0);
            $s = (int) ($sessions[$d] ?? 0);
            $p = (int) ($participations[$d] ?? 0);

            $series['loans'][] = $l;
            $series['returns'][] = $ret;
            $series['onlineReadings'][] = $r;
            $series['computerSessions'][] = $s;
            $series['eventParticipations'][] = $p;
            $series['total'][] = $l + $r + $s + $p;
        }

        return ['dates' => $dates, ...$series];
    }

    /**
     * Reader count per nationality, label => count, sorted by size. Only the
     * top 7 are kept distinct — the rest are folded into "Boshqa" so the
     * chart stays readable regardless of how many distinct nationalities
     * are on file. Missing nationality is its own "Noma'lum" bucket.
     *
     * @return array<string, int>
     */
    private function readersByNationality(): array
    {
        $counts = Reader::query()->selectRaw('nationality, COUNT(*) as c')->groupBy('nationality')->pluck('c', 'nationality');

        $named = [];
        foreach ($counts as $nationality => $c) {
            $label = filled($nationality) ? $nationality : __('Noma’lum');
            $named[$label] = ($named[$label] ?? 0) + (int) $c;
        }

        arsort($named);

        $top = array_slice($named, 0, 7, true);
        $rest = array_sum(array_slice($named, 7, null, true));
        if ($rest > 0) {
            $top[__('Boshqa')] = $rest;
        }

        return $top;
    }

    /**
     * Book/copy counts per top-level category (e.g. o‘quv-uslubiy, ilmiy,
     * xorijiy, badiiy adabiyotlar), each rolled up to include every
     * descendant sub-category — mirrors the catalog's "parent count
     * includes its children" convention. Whatever top-level categories
     * exist drives the chart; nothing here is hardcoded to specific names.
     *
     * @param  array{0: \Illuminate\Support\Collection<int, Category>, 1: \Closure(int): array<int, int>}  $categoryRollup
     * @return array{labels: array<int, string>, titles: array<int, int>, copies: array<int, int>}
     */
    private function literatureByCategory(array $categoryRollup): array
    {
        [$topCategories, $descendantIds] = $categoryRollup;

        $labels = $titles = $copies = [];

        foreach ($topCategories as $category) {
            $bookIds = Book::query()
                ->join('book_category', 'book_category.book_id', '=', 'books.id')
                ->whereIn('book_category.category_id', $descendantIds($category->id))
                ->distinct()
                ->pluck('books.id');

            $labels[] = $category->name;
            $titles[] = $bookIds->count();
            $copies[] = BookCopy::whereIn('book_id', $bookIds)->count();
        }

        return ['labels' => $labels, 'titles' => $titles, 'copies' => $copies];
    }

    /**
     * Per-year expenses, two lines: books (kirish akti sanasi x Book.price,
     * same acquisition-date convention as annualAcquisitionReport() — a
     * copy with no acquisition date, or a book with no price, contributes
     * nothing) and periodicals (subscription amount, but ONLY the ones paid
     * from the branch's own budget — a reader-paid subscription is the
     * reader's money, not a library expense). Zero-filled across every
     * year in the combined min/max range of both sources.
     *
     * @return array{years: array<int, int>, books: array<int, float>, subscriptions: array<int, float>}
     */
    private function annualExpensesReport(): array
    {
        $bookExpenses = DB::table('book_copies')
            ->join('books', 'books.id', '=', 'book_copies.book_id')
            ->whereNotNull('book_copies.acquisition_act_at')
            ->whereNotNull('books.price')
            ->selectRaw('YEAR(book_copies.acquisition_act_at) as year, SUM(books.price) as total')
            ->groupBy('year')
            ->pluck('total', 'year');

        $subscriptionExpenses = Subscription::query()
            ->where('source', SubscriptionSource::Budget->value)
            ->selectRaw('year, SUM(amount) as total')
            ->groupBy('year')
            ->pluck('total', 'year');

        $years = $bookExpenses->keys()->merge($subscriptionExpenses->keys())->map(fn ($y) => (int) $y)->unique()->sort()->values();

        if ($years->isEmpty()) {
            return ['years' => [], 'books' => [], 'subscriptions' => []];
        }

        $allYears = range($years->first(), $years->last());

        return [
            'years' => $allYears,
            'books' => collect($allYears)->map(fn ($y) => round((float) ($bookExpenses[$y] ?? 0), 2))->all(),
            'subscriptions' => collect($allYears)->map(fn ($y) => round((float) ($subscriptionExpenses[$y] ?? 0), 2))->all(),
        ];
    }
}
