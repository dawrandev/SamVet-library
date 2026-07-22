<?php

namespace App\Services;

use App\Data\SubscriptionData;
use App\Models\DeliveryLocation;
use App\Models\Journal;
use App\Models\PostBranch;
use App\Models\Reader;
use App\Models\Subscription;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SubscriptionService
{
    private const RECEIPTS_DIR = 'subscriptions/receipts';

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
            'readers' => Reader::with(['affiliationPlace', 'affiliationUnit', 'affiliationGroup'])
                ->orderBy('full_name')
                ->get(['id', 'full_name', 'type', 'affiliation_place_id', 'affiliation_unit_id', 'affiliation_group_id']),
            'journals' => Journal::orderBy('name')->get(['id', 'name', 'kind', 'index']),
            'deliveryLocations' => DeliveryLocation::orderBy('name')->get(['id', 'name']),
            'postBranches' => PostBranch::orderBy('name')->get(['id', 'name']),
        ];
    }

    public function create(SubscriptionData $data): Subscription
    {
        return DB::transaction(function () use ($data) {
            $attributes = $data->toAttributes();

            if ($data->receipt_file) {
                $attributes['receipt_file'] = $this->storeProtected($data->receipt_file);
            }

            return $this->subscriptions->create($attributes);
        });
    }

    public function update(Subscription $subscription, SubscriptionData $data): Subscription
    {
        return DB::transaction(function () use ($subscription, $data) {
            $attributes = $data->toAttributes();

            if ($data->receipt_file) {
                $this->deleteFile($subscription->receipt_file);
                $attributes['receipt_file'] = $this->storeProtected($data->receipt_file);
            }

            return $this->subscriptions->update($subscription, $attributes);
        });
    }

    public function delete(Subscription $subscription): void
    {
        DB::transaction(function () use ($subscription) {
            $this->deleteFile($subscription->receipt_file);
            $this->subscriptions->delete($subscription);
        });
    }

    private function storeProtected(UploadedFile $file): string
    {
        return $file->store(self::RECEIPTS_DIR, 'local');
    }

    private function deleteFile(?string $path): void
    {
        if ($path && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }
}
