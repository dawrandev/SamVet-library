<?php

namespace App\Repositories\Contracts;

use App\Models\ComputerSession;

interface ComputerSessionRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ComputerSession;

    public function delete(ComputerSession $session): void;
}
