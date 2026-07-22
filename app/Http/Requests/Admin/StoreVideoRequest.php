<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is under `auth` middleware. If roles are added — VideoPolicy.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'annotation' => ['nullable', 'string'],
            'cover' => ['nullable', 'image', 'max:2048'], // 2 MB
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('Video nomi'),
            'author' => __('Muallifi'),
            'annotation' => __('Annotatsiyasi'),
            'cover' => __('Muqova rasmi'),
        ];
    }
}
