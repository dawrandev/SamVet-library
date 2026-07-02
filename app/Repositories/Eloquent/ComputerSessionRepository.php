<?php

namespace App\Repositories\Eloquent;

use App\Models\ComputerSession;
use App\Repositories\Contracts\ComputerSessionRepositoryInterface;

class ComputerSessionRepository implements ComputerSessionRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ComputerSession
    {
        return ComputerSession::create($data);
    }

    public function delete(ComputerSession $session): void
    {
        $session->delete();
    }
}
