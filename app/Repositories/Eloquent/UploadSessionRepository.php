<?php

namespace App\Repositories\Eloquent;

use App\Models\UploadSession;
use App\Repositories\Contracts\UploadSessionRepositoryInterface;
use Illuminate\Support\Collection;

class UploadSessionRepository implements UploadSessionRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): UploadSession
    {
        return UploadSession::create($data);
    }

    public function findByToken(string $token): ?UploadSession
    {
        return UploadSession::where('token', $token)->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(UploadSession $session, array $data): UploadSession
    {
        $session->update($data);

        return $session;
    }

    public function delete(UploadSession $session): void
    {
        $session->delete();
    }

    public function staleForAdmin(int $adminId, int $olderThanHours): Collection
    {
        return UploadSession::where('admin_id', $adminId)
            ->where('created_at', '<', now()->subHours($olderThanHours))
            ->get();
    }
}
