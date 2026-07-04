<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreJournalIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Marshrut `auth` middleware ostida. Rollar qo'shilsa — Policy.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxYear = (int) date('Y') + 1;

        return [
            'year' => ['required', 'integer', 'min:1000', "max:{$maxYear}"],
            'issue_number' => ['required', 'string', 'max:100'],
            'pages' => ['nullable', 'integer', 'min:1'],
            'cover' => ['nullable', 'image', 'max:2048'],                 // 2 MB
            'electronic_file' => ['nullable', 'mimes:pdf', 'max:51200'],  // 50 MB
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'year' => __('Nashr yili'),
            'issue_number' => __('Soni'),
            'pages' => __('Sahifalar soni'),
            'cover' => __('Muqova rasmi'),
            'electronic_file' => __('Elektron fayl'),
        ];
    }
}
