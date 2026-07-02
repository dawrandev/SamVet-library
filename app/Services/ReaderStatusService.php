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
     * A'zoni bloklash. $blockedUntil null bo'lsa — butunlay bloklash.
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
     * Foydalanishni tugatish (bitirgan / ishdan bo'shagan) — status=left.
     * Yozuv saqlanadi, asosiy ro'yxatda ko'rinmaydi.
     */
    public function finish(Reader $reader): Reader
    {
        return $this->readers->update($reader, [
            'status' => ReaderStatus::Left->value,
        ]);
    }

    /**
     * Foydalanuvchini tiklash — status=active, bloklash tozalanadi.
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
