<?php

namespace App\Data;

use Illuminate\Http\Request;

/**
 * DTO for passing data from Controller → Service (computer session).
 * Deliberately holds no `location`, `reader_id`, `issued_at` or `expires_at`
 * property — those are always server-derived in the Service, never trusted
 * from client input.
 */
class ComputerSessionData
{
    public function __construct(
        public readonly int $computer_id,
        public readonly int $duration_minutes,
        public readonly ?string $purpose,
        public readonly ?string $note,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            computer_id: $request->integer('computer_id'),
            duration_minutes: $request->integer('duration_minutes'),
            purpose: $request->input('purpose') ?: null,
            note: $request->input('note') ?: null,
        );
    }
}
