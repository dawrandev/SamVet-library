<?php

namespace App\Services;

use App\Data\ComputerSessionData;
use App\Models\Computer;
use App\Models\ComputerSession;
use App\Models\Reader;
use App\Repositories\Contracts\ComputerSessionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ComputerSessionService
{
    /** Cache of the expired-and-unfinished sessions count (navbar/sidebar notification). */
    public const EXPIRED_CACHE_KEY = 'expired_computer_sessions_count';

    public function __construct(
        private readonly ComputerSessionRepositoryInterface $sessions,
    ) {}

    /**
     * List of computer usage records (active / expired / finished).
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return $this->sessions->paginate($filters);
    }

    /**
     * Count of expired-but-unfinished sessions (for the notification).
     */
    public function expiredCount(): int
    {
        return $this->sessions->expiredCount();
    }

    /**
     * Check a computer out to a reader for an allotted duration.
     * issued_at/expires_at/location are all server-derived — never trusted
     * from client input (see ComputerSessionData).
     */
    public function create(Reader $reader, ComputerSessionData $data): ComputerSession
    {
        $computer = Computer::findOrFail($data->computer_id);
        $issuedAt = now();

        return DB::transaction(fn () => $this->sessions->create([
            'reader_id' => $reader->id,
            'computer_id' => $computer->id,
            'issued_at' => $issuedAt,
            'expires_at' => $issuedAt->clone()->addMinutes($data->duration_minutes),
            'duration_minutes' => $data->duration_minutes,
            'location' => $computer->location,
            'purpose' => $data->purpose,
            'note' => $data->note,
        ]));
    }

    /**
     * "Tugatish" — the reader finished using the computer (early, on time, or late).
     * A no-op if already finished (mirrors LoanService::returnLoan's guard).
     */
    public function finish(ComputerSession $session): ComputerSession
    {
        if ($session->isFinished()) {
            return $session;
        }

        $session = DB::transaction(function () use ($session) {
            $this->sessions->update($session, ['returned_at' => now()]);

            return $session;
        });

        $this->forgetExpiredCache();

        return $session;
    }

    /**
     * Grant more time on an active session. If it's still running, extends
     * from its current expiry (so remaining time isn't lost); if it already
     * ran out, extends from now (the librarian is granting fresh time).
     * A no-op if already finished.
     */
    public function extend(ComputerSession $session, int $minutes): ComputerSession
    {
        if ($session->isFinished()) {
            return $session;
        }

        $session = DB::transaction(function () use ($session, $minutes) {
            $base = ($session->expires_at !== null && $session->expires_at->isFuture())
                ? $session->expires_at
                : now();

            $this->sessions->update($session, [
                'expires_at' => $base->clone()->addMinutes($minutes),
                'duration_minutes' => ($session->duration_minutes ?? 0) + $minutes,
            ]);

            return $session;
        });

        $this->forgetExpiredCache();

        return $session;
    }

    public function delete(ComputerSession $session): void
    {
        DB::transaction(fn () => $this->sessions->delete($session));

        // Deleting an expired-unfinished row can change the notification count.
        $this->forgetExpiredCache();
    }

    /**
     * Invalidates the expired-sessions cache — so the navbar/sidebar
     * notification updates immediately after a state change.
     */
    private function forgetExpiredCache(): void
    {
        Cache::forget(self::EXPIRED_CACHE_KEY);
    }
}
