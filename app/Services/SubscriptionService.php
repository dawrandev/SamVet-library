<?php

namespace App\Services;

use App\Data\SubscriptionData;
use App\Models\DeliveryLocation;
use App\Models\Journal;
use App\Models\Reader;
use App\Models\Subscription;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptions,
    ) {}

    /**
     * Paginated, filtered list.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->subscriptions->paginate($filters, $perPage);
    }

    /**
     * Total subscription amount for the given filters (report figure).
     *
     * @param  array<string, mixed>  $filters
     */
    public function sumAmount(array $filters = []): float
    {
        return $this->subscriptions->sumAmount($filters);
    }

    /**
     * Options for the filter dropdowns and the create/edit form.
     *
     * @return array<string, mixed>
     */
    public function formOptions(): array
    {
        return [
            // affiliation_* / index — shown as a read-only reference once a reader/journal is picked.
            'readers' => Reader::orderBy('full_name')->get(['id', 'full_name', 'type', 'affiliation_place', 'affiliation_unit', 'affiliation_group']),
            'journals' => Journal::orderBy('name')->get(['id', 'name', 'kind', 'index']),
            'deliveryLocations' => DeliveryLocation::orderBy('name')->get(['id', 'name']),
        ];
    }

    /**
     * Per-journal breakdown for the given year (Table 1 / "Tahlil"):
     * how many subscriptions, and which of the 12 months they cover.
     *
     * @return list<array{journal: Journal, months: array<int, int>, count: int, percentage: int}>
     */
    public function journalCoverage(int $year): array
    {
        $byJournal = Subscription::where('year', $year)->get()->groupBy('journal_id');

        return Journal::orderBy('name')->get()
            ->map(function (Journal $journal) use ($byJournal) {
                $rows = $byJournal->get($journal->id, collect());
                $months = array_fill(1, 12, 0);

                foreach ($rows as $row) {
                    for ($m = $row->start_month->value; $m <= $row->end_month->value; $m++) {
                        $months[$m]++;
                    }
                }

                $coveredMonths = count(array_filter($months, fn (int $count) => $count > 0));

                return [
                    'journal' => $journal,
                    'months' => $months,
                    'count' => $rows->count(),
                    'percentage' => (int) round($coveredMonths / 12 * 100),
                ];
            })
            ->filter(fn (array $row) => $row['count'] > 0)
            ->values()
            ->all();
    }

    public function create(SubscriptionData $data): Subscription
    {
        return DB::transaction(function () use ($data) {
            return $this->subscriptions->create($data->toAttributes());
        });
    }

    public function update(Subscription $subscription, SubscriptionData $data): Subscription
    {
        return DB::transaction(function () use ($subscription, $data) {
            return $this->subscriptions->update($subscription, $data->toAttributes());
        });
    }

    public function delete(Subscription $subscription): void
    {
        DB::transaction(function () use ($subscription) {
            $this->subscriptions->delete($subscription);
        });
    }
}
