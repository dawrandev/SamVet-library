<?php

namespace App\Services;

use App\Enums\ReaderStatus;
use App\Models\Reader;
use App\Repositories\Contracts\ReaderRepositoryInterface;

class ReaderStatusService
{
    public function __construct(
        private readonly ReaderRepositoryInterface $readers,
    ) {}

    /**
     * Block a reader. If $blockedUntil is null — block permanently.
     */
    public function block(Reader $reader, ?string $blockedUntil = null, ?string $reason = null): Reader
    {
        return $this->readers->update($reader, [
            'status' => ReaderStatus::Blocked->value,
            'blocked_until' => $blockedUntil,
            'block_reason' => $reason,
        ]);
    }

    /**
     * End usage (graduated / left employment) — status=left.
     * The record is kept but does not appear in the main list.
     */
    public function finish(Reader $reader): Reader
    {
        return $this->readers->update($reader, [
            'status' => ReaderStatus::Left->value,
        ]);
    }

    /**
     * Restore a reader — status=active, block is cleared.
     */
    public function restore(Reader $reader): Reader
    {
        return $this->readers->update($reader, [
            'status' => ReaderStatus::Active->value,
            'blocked_until' => null,
            'block_reason' => null,
        ]);
    }
}
