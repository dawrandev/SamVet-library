<?php

namespace App\Repositories\Eloquent;

use App\Models\ComputerSession;
use App\Repositories\Contracts\ComputerSessionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ComputerSessionRepository implements ComputerSessionRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ComputerSession
    {
        return ComputerSession::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ComputerSession $session, array $data): ComputerSession
    {
        $session->update($data);

        return $session;
    }

    public function delete(ComputerSession $session): void
    {
        $session->delete();
    }

    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $scope = $filters['scope'] ?? 'active';
        $search = trim((string) ($filters['search'] ?? ''));
        $now = now();

        $query = ComputerSession::query()->with(['reader', 'computer']);

        match ($scope) {
            'finished' => $query->whereNotNull('returned_at')->orderByDesc('returned_at'),
            'expired' => $query->whereNull('returned_at')
                ->whereNotNull('expires_at')->where('expires_at', '<', $now)
                ->orderBy('expires_at'),
            default => $query->whereNull('returned_at') // active
                ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', $now))
                ->orderBy('expires_at'),
        };

        if ($search !== '') {
            $query->where(function (Builder $q) use ($search) {
                $q->whereHas('reader', fn (Builder $r) => $r->where('full_name', 'like', "%{$search}%"))
                    ->orWhereHas('computer', fn (Builder $c) => $c->where('computer_number', 'like', "%{$search}%"));
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function expiredCount(): int
    {
        return ComputerSession::query()
            ->whereNull('returned_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->count();
    }
}
