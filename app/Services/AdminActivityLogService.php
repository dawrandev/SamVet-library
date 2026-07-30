<?php

namespace App\Services;

use App\Enums\AdminActivityAction;
use App\Repositories\Contracts\AdminActivityLogRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AdminActivityLogService
{
    /**
     * Values that never get written to the log verbatim, even though the
     * field name itself is worth recording (e.g. "password was changed").
     *
     * @var list<string>
     */
    private const REDACTED_FIELDS = ['password', 'remember_token'];

    public function __construct(
        private readonly AdminActivityLogRepositoryInterface $logs,
    ) {}

    /**
     * Records an admin action on a model, called from a model Observer.
     * Silently skipped outside a signed-in admin session (console/seeder/queue
     * writes aren't admin activity) and for a no-op update (e.g. a save()
     * that only touched `updated_at`) — nothing worth showing either way.
     *
     * Explicitly checks the `web` guard, not the guard-less auth()/auth()->id()
     * — Laravel's actingAs($reader, 'reader') (used throughout the test suite,
     * and reachable in production whenever a reader-facing action runs a
     * tracked model's Observer) changes the *default* guard for the rest of
     * that request, so an unguarded check can silently pick up a reader's id
     * and try to store it as an admin — a real bug caught via a genuine FK
     * violation in tests/Feature/OnlineReaderTest.php.
     */
    public function logChange(Model $model, AdminActivityAction $action): void
    {
        if (! auth('web')->check()) {
            return;
        }

        $changes = match ($action) {
            AdminActivityAction::Created => Collection::make($model->getAttributes())
                ->only($model->getFillable())
                ->mapWithKeys(fn ($value, $key) => [$key => [null, $value]])
                ->all(),
            AdminActivityAction::Updated => Collection::make($model->getChanges())
                ->except(['updated_at'])
                ->mapWithKeys(fn ($value, $key) => [$key => [$model->getOriginal($key), $value]])
                ->all(),
            AdminActivityAction::Deleted => [],
        };

        if ($changes === [] && $action === AdminActivityAction::Updated) {
            return;
        }

        $this->logs->create([
            'admin_id' => auth('web')->id(),
            'action' => $action,
            'subject_type' => class_basename($model),
            'subject_id' => $model->getKey(),
            'changes' => $changes !== [] ? $this->redact($changes) : null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return $this->logs->paginate($filters);
    }

    /**
     * @param  array<string, array{0: mixed, 1: mixed}>  $changes
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    private function redact(array $changes): array
    {
        foreach (self::REDACTED_FIELDS as $field) {
            if (array_key_exists($field, $changes)) {
                $changes[$field] = ['[hidden]', '[hidden]'];
            }
        }

        return $changes;
    }
}
