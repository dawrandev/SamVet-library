<?php

namespace App\Services;

use App\Data\AudioTrackData;
use App\Enums\ChunkedUploadKind;
use App\Models\Audiobook;
use App\Models\AudioTrack;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AudioTrackService
{
    /** Protected disk (local, NOT public) — mirrors journals/electronic. */
    private const AUDIO_DIR = 'audiobooks/audio';

    public function __construct(
        private readonly ChunkedUploadService $chunkedUploads,
    ) {}

    /**
     * Either a direct upload (audio_file) or one assembled via chunked upload
     * (audio_file_token) — never both, the FormRequest guards that. Null when
     * neither was given (e.g. an update that only renamed the track).
     */
    private function resolveAudioFile(AudioTrackData $data): ?string
    {
        if ($data->audio_file_token) {
            return $this->chunkedUploads->claimAndMove(
                $data->audio_file_token,
                ChunkedUploadKind::Audio,
                Auth::guard('web')->user(),
                self::AUDIO_DIR
            );
        }

        return $data->audio_file ? $this->storeProtected($data->audio_file) : null;
    }

    public function create(Audiobook $audiobook, AudioTrackData $data): AudioTrack
    {
        return DB::transaction(function () use ($audiobook, $data) {
            $attributes = $data->toAttributes();
            $attributes['audiobook_id'] = $audiobook->id;
            $attributes['sort_order'] = ((int) $audiobook->tracks()->max('sort_order')) + 1;

            if ($path = $this->resolveAudioFile($data)) {
                $attributes['audio_file'] = $path;
            }

            return $audiobook->tracks()->create($attributes);
        });
    }

    public function update(AudioTrack $track, AudioTrackData $data): AudioTrack
    {
        return DB::transaction(function () use ($track, $data) {
            $attributes = $data->toAttributes();

            if ($path = $this->resolveAudioFile($data)) {
                $this->deleteFile($track->audio_file);
                $attributes['audio_file'] = $path;
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
