<?php

namespace App\Services;

use App\Models\ComputerSession;
use App\Models\Reader;
use App\Repositories\Contracts\ComputerSessionRepositoryInterface;

class ComputerSessionService
{
    public function __construct(
        private readonly ComputerSessionRepositoryInterface $sessions,
    ) {}

    /**
     * A'zoning kompyuterdan foydalanish yozuvini qo'shish.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(Reader $reader, array $data): ComputerSession
    {
        return $this->sessions->create([
            'reader_id' => $reader->id,
            'date' => $data['date'],
            'issued_time' => $data['issued_time'] ?? null,
            'returned_time' => $data['returned_time'] ?? null,
            'computer_number' => $data['computer_number'] ?? null,
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
