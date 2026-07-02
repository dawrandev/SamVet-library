<?php

namespace App\Services;

use App\Enums\WarningReason;
use App\Models\Reader;
use App\Models\ReaderWarning;
use App\Repositories\Contracts\WarningRepositoryInterface;

class WarningService
{
    public function __construct(
        private readonly WarningRepositoryInterface $warnings,
    ) {}

    /**
     * A'zoga ogohlantirish qo'shish (qizil qoidalar bo'yicha).
     */
    public function add(Reader $reader, WarningReason|string $reason, ?string $note = null): ReaderWarning
    {
        $reasonValue = $reason instanceof WarningReason ? $reason->value : $reason;

        return $this->warnings->create([
            'reader_id' => $reader->id,
            'reason' => $reasonValue,
            'note' => $note,
            'warned_at' => now()->toDateString(),
            'created_by' => auth()->id(),
        ]);
    }

    public function delete(ReaderWarning $warning): void
    {
        $this->warnings->delete($warning);
    }
}
