<?php

namespace App\Data;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * DTO for passing data from Controller → Service (video track). `video_file`
 * is nullable here even though it's required on create (enforced by
 * StoreVideoTrackRequest) — on update it's optional, replacing the file only
 * when a new one is uploaded, mirroring AudioTrackData/audio_file.
 */
class VideoTrackData
{
    public function __construct(
        public readonly string $title,
        public readonly ?UploadedFile $video_file,
        /** Set instead of $video_file when the video was uploaded via chunked upload — see ChunkedUploadService. */
        public readonly ?string $video_file_token = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            title: $request->string('title')->toString(),
            video_file: $request->file('video_file'),
            video_file_token: $request->input('video_file_token') ?: null,
        );
    }

    /**
     * Only the scalar fields written to the video_tracks table (without the file).
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'title' => $this->title,
        ];
    }
}
