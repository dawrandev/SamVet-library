<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAudioTrackRequest extends FormRequest
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
            'audio_file' => ['nullable', 'mimes:mp3,mpga,wav', 'max:102400'], // 100 MB
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
