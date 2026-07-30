<?php

namespace App\Repositories\Contracts;

use App\Models\UploadSession;
use Illuminate\Support\Collection;

interface UploadSessionRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): UploadSession;

    public function findByToken(string $token): ?UploadSession;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(UploadSession $session, array $data): UploadSession;

    public function delete(UploadSession $session): void;

    /**
     * Sessions belonging to the admin that are older than the given hours
     * and were never claimed — candidates for lazy cleanup.
     *
     * @return Collection<int, UploadSession>
     */
    public function staleForAdmin(int $adminId, int $olderThanHours): Collection;
}
