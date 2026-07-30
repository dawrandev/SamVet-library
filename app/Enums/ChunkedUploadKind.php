<?php

namespace App\Enums;

/**
 * The validation profile for a chunked upload — mirrors the mimes/extensions
 * + max-size rules already enforced by the entity FormRequests (e.g.
 * StoreBookRequest's `electronic_file`), reused both to cap the declared
 * total_size at ChunkedUploadService::start() and to re-validate the
 * assembled file at ChunkedUploadService::finish().
 */
enum ChunkedUploadKind: string
{
    case Pdf = 'pdf';
    case Video = 'video';
    case Audio = 'audio';

    public function maxKilobytes(): int
    {
        return match ($this) {
            self::Pdf, self::Video => 972_800, // 950 MB
            self::Audio => 102_400, // 100 MB
        };
    }

    /** Laravel validation rule for the assembled file (mirrors the matching FormRequest). */
    public function validationRule(): string
    {
        return match ($this) {
            self::Pdf => 'mimes:pdf',
            self::Video => 'mimes:mp4,mov,webm,avi,mkv',
            // Same rationale as StoreAudioTrackRequest: `extensions`, not `mimes` —
            // libmagic misreads some ID3v2-tagged MP3s. Admin-only upload, never executed.
            self::Audio => 'extensions:mp3,mpga,wav,m4a,aac,ogg',
        };
    }
}
