<?php

namespace App\Services;

use App\Enums\CopyStatus;
use App\Enums\LoanStatus;
use App\Enums\ReaderStatus;
use App\Models\Article;
use App\Models\Author;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\BookReading;
use App\Models\Category;
use App\Models\Computer;
use App\Models\Journal;
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
    public function stats(?string $from = null, ?string $to = null): array
    {
        // Status breakdowns in a single grouped query each (no per-status count queries).
        $copiesByStatus = BookCopy::query()->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');
        $readersByType = Reader::query()->selectRaw('type, COUNT(*) as c')->groupBy('type')->pluck('c', 'type');
        $computersByStatus = Computer::query()->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

        $overdue = Loan::query()
            ->where('status', LoanStatus::OnLoan->value)
            ->whereNotNull('due_at')
            ->whereDate('due_at', '<', Carbon::today())
            ->count();

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
            'loansActive' => Loan::where('status', LoanStatus::OnLoan->value)->count(),
            'overdue' => $overdue,

            // Breakdowns (value => count)
            'copiesByStatus' => $copiesByStatus,
            'readersByType' => $readersByType,
            'computersByStatus' => $computersByStatus,

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
