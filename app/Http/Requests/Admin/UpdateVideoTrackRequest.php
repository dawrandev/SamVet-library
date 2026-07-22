<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVideoTrackRequest extends FormRequest
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
            // Optional on update — only replaces the file when a new one is uploaded.
            'video_file' => ['nullable', 'mimes:mp4,mov,webm,avi,mkv', 'max:972800'], // 950 MB
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
        ];
    }
}
