<?php

namespace App\Services;

use App\Data\AudioTrackData;
use App\Models\Audiobook;
use App\Models\AudioTrack;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AudioTrackService
{
    /** Protected disk (local, NOT public) — mirrors journals/electronic. */
    private const AUDIO_DIR = 'audiobooks/audio';

    public function create(Audiobook $audiobook, AudioTrackData $data): AudioTrack
    {
        return DB::transaction(function () use ($audiobook, $data) {
            $attributes = $data->toAttributes();
            $attributes['audiobook_id'] = $audiobook->id;
            $attributes['sort_order'] = ((int) $audiobook->tracks()->max('sort_order')) + 1;

            if ($data->audio_file) {
                $attributes['audio_file'] = $this->storeProtected($data->audio_file);
            }

            return $audiobook->tracks()->create($attributes);
        });
    }

    public function update(AudioTrack $track, AudioTrackData $data): AudioTrack
    {
        return DB::transaction(function () use ($track, $data) {
            $attributes = $data->toAttributes();

            if ($data->audio_file) {
                $this->deleteFile($track->audio_file);
                $attributes['audio_file'] = $this->storeProtected($data->audio_file);
            }

            $track->update($attributes);

            return $track;
        });
    }

    public function delete(AudioTrack $track): void
    {
        DB::transaction(function () use ($track) {
            $this->deleteFile($track->audio_file);
            $track->delete();
        });
    }

    /** Removes only the stored file — used when the parent Audiobook itself is being deleted (row cascades in DB). */
    public function deleteFileOnly(AudioTrack $track): void
    {
        $this->deleteFile($track->audio_file);
    }

    private function storeProtected(UploadedFile $file): string
    {
        return $file->store(self::AUDIO_DIR, 'local');
    }

    private function deleteFile(?string $path): void
    {
        if ($path && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }
}
