<?php

namespace App\Data;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * DTO for passing data from Controller → Service (audio track). `audio_file`
 * is nullable here even though it's required on create (enforced by
 * StoreAudioTrackRequest) — on update it's optional, replacing the file only
 * when a new one is uploaded, mirroring JournalIssueData/electronic_file.
 */
class AudioTrackData
{
    public function __construct(
        public readonly string $title,
        public readonly ?UploadedFile $audio_file,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            title: $request->string('title')->toString(),
            audio_file: $request->file('audio_file'),
        );
    }

    /**
     * Only the scalar fields written to the audio_tracks table (without the file).
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
