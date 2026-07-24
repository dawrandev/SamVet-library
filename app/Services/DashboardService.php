<?php

namespace App\Services;

use App\Enums\CopyStatus;
use App\Enums\Gender;
use App\Enums\ReaderStatus;
use App\Enums\SubscriptionSource;
use App\Models\Article;
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
    public function stats(?string $from = null, ?string $to = null, ?string $statsFrom = null, ?string $statsTo = null): array
    {
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

        $readersByType = Reader::query()->selectRaw('type, COUNT(*) as c')->groupBy('type')->pluck('c', 'type');

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

            // Reader demographics (bar charts, not donuts — see dashboard view)
            'readersByGender' => $this->readersByGender(),
            'readersByNationality' => $this->readersByNationality(),
            'readersByAgeGroup' => $this->readersByAgeGroup(),
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
     * issued, online reads, computer sessions, event participations, and
     * their daily sum — every day in the range present, zero-filled.
     *
     * @return array{dates: array<int, string>, loans: array<int, int>, onlineReadings: array<int, int>, computerSessions: array<int, int>, eventParticipations: array<int, int>, total: array<int, int>}
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

        $series = ['loans' => [], 'onlineReadings' => [], 'computerSessions' => [], 'eventParticipations' => [], 'total' => []];

        foreach ($dates as $d) {
            $l = (int) ($loans[$d] ?? 0);
            $r = (int) ($readings[$d] ?? 0);
            $s = (int) ($sessions[$d] ?? 0);
            $p = (int) ($participations[$d] ?? 0);

            $series['loans'][] = $l;
            $series['onlineReadings'][] = $r;
            $series['computerSessions'][] = $s;
            $series['eventParticipations'][] = $p;
            $series['total'][] = $l + $r + $s + $p;
        }

        return ['dates' => $dates, ...$series];
    }

    /**
     * Reader count per gender, label => count. Missing gender is bucketed
     * under "Noma'lum" rather than dropped, so the chart total still matches
     * the reader total.
     *
     * @return array<string, int>
     */
    private function readersByGender(): array
    {
        $counts = Reader::query()->selectRaw('gender, COUNT(*) as c')->groupBy('gender')->pluck('c', 'gender');

        $result = [];
        foreach (Gender::cases() as $gender) {
            $c = (int) ($counts[$gender->value] ?? 0);
            if ($c > 0) {
                $result[$gender->label()] = $c;
            }
        }

        // Anything not matching a known Gender case (including NULL) falls here too.
        $unknown = (int) $counts->sum() - array_sum($result);
        if ($unknown > 0) {
            $result[__('Noma’lum')] = $unknown;
        }

        return $result;
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

    /** Age buckets in display order — internal to this chart, not a shared domain concept. */
    private const AGE_BUCKETS = [
        ['max' => 17, 'label' => '<18'],
        ['max' => 25, 'label' => '18-25'],
        ['max' => 35, 'label' => '26-35'],
        ['max' => 45, 'label' => '36-45'],
        ['max' => 60, 'label' => '46-60'],
        ['max' => null, 'label' => '60+'],
    ];

    /**
     * Reader count per age bucket, label => count, in bucket order. Readers
     * without a birth date are bucketed under "Noma'lum" at the end.
     *
     * @return array<string, int>
     */
    private function readersByAgeGroup(): array
    {
        $result = array_fill_keys(array_column(self::AGE_BUCKETS, 'label'), 0);

        Reader::query()->whereNotNull('birth_date')->pluck('birth_date')->each(function ($birthDate) use (&$result): void {
            $age = Carbon::parse($birthDate)->age;
            foreach (self::AGE_BUCKETS as $bucket) {
                if ($bucket['max'] === null || $age <= $bucket['max']) {
                    $result[$bucket['label']]++;
                    break;
                }
            }
        });

        $result = array_filter($result);

        $unknown = Reader::query()->whereNull('birth_date')->count();
        if ($unknown > 0) {
            $result[__('Noma’lum')] = $unknown;
        }

        return $result;
    }
}
