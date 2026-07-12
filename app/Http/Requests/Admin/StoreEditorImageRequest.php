<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreEditorImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is under `auth` middleware. If roles are added — Policy.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // SVG is excluded (may contain JS — stored XSS), matching the news cover rule.
            'file' => ['required', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'], // 5 MB
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'file' => __('Rasm'),
        ];
    }
}
