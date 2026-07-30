<?php

namespace App\Http\Requests\Admin;

use App\Enums\ChunkedUploadKind;
use App\Services\ChunkedUploadService;
use Illuminate\Foundation\Http\FormRequest;

class StoreVideoTrackRequest extends FormRequest
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
            // Required unless the video went through the chunked-upload flow instead.
            'video_file' => ['required_without:video_file_token', 'mimes:mp4,mov,webm,avi,mkv', 'max:972800'], // 950 MB
            'video_file_token' => ['required_without:video_file', 'uuid', function ($attribute, $value, $fail) {
                if ($value && ! app(ChunkedUploadService::class)->isClaimable($value, ChunkedUploadKind::Video, $this->user())) {
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
            'video_file' => __('Video fayl'),
            'video_file_token' => __('Video fayl'),
        ];
    }
}
