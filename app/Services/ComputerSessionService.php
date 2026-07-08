<?php

namespace App\Services;

use App\Models\Computer;
use App\Models\ComputerSession;
use App\Models\Reader;
use App\Repositories\Contracts\ComputerSessionRepositoryInterface;

class ComputerSessionService
{
    public function __construct(
        private readonly ComputerSessionRepositoryInterface $sessions,
    ) {}

    /**
     * Add a computer usage record for a reader.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(Reader $reader, array $data): ComputerSession
    {
        $computerId = $data['computer_id'] ?? null;
        $computerNumber = $data['computer_number'] ?? null;

        // Snapshot the inventory number onto the session, so the usage log keeps the
        // machine reference even if the computer is later removed from the registry.
        if ($computerId !== null) {
            $computerNumber = Computer::find($computerId)?->inventory_number ?? $computerNumber;
        }

        return $this->sessions->create([
            'reader_id' => $reader->id,
            'date' => $data['date'],
            'issued_time' => $data['issued_time'] ?? null,
            'returned_time' => $data['returned_time'] ?? null,
            'computer_number' => $computerNumber, // snapshot of the picked computer (or legacy free-text)
            'computer_id' => $computerId,
            'location' => $data['location'] ?? null,
            'purpose' => $data['purpose'] ?? null,
            'note' => $data['note'] ?? null,
        ]);
    }

    public function delete(ComputerSession $session): void
    {
        $this->sessions->delete($session);
    }
}
