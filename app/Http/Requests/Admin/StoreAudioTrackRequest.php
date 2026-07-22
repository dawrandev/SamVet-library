<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAudioTrackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            // `extensions` (not `mimes`) — `mimes` re-detects the type from
            // the file's own bytes via PHP's fileinfo/libmagic, which
            // regularly misreads real MP3s (ID3v2 tags with embedded cover
            // art especially) and rejects genuine audiobook files. Safe here
            // since the upload is admin-only and the file is never executed —
            // just stored on the protected disk and streamed back byte-for-byte.
            'audio_file' => ['required', 'file', 'extensions:mp3,mpga,wav,m4a,aac,ogg', 'max:102400'], // 100 MB
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => __('Nomi'),
            'audio_file' => __('Audio fayl'),
        ];
    }
}
