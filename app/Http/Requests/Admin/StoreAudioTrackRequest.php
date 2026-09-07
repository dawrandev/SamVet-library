<?php

namespace App\Http\Requests\Admin;

use App\Enums\ChunkedUploadKind;
use App\Services\ChunkedUploadService;
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
            // Required unless the track went through the chunked-upload flow instead.
            'audio_file' => ['required_without:audio_file_token', 'file', 'extensions:mp3,mpga,wav,m4a,aac,ogg', 'max:102400'], // 100 MB
            'audio_file_token' => ['required_without:audio_file', 'uuid', function ($attribute, $value, $fail) {
                if ($value && ! app(ChunkedUploadService::class)->isClaimable($value, ChunkedUploadKind::Audio, $this->user())) {
                    $fail(__('Fayl yuklash sessiyasi topilmadi yoki muddati tugagan. Iltimos, faylni qayta yuklang.'));
                }
            }],
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
            'audio_file_token' => __('Audio fayl'),
        ];
    }
}
