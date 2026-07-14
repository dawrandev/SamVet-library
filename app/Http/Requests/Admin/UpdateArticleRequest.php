<?php

namespace App\Http\Requests\Admin;

use App\Enums\ArticleCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateArticleRequest extends FormRequest
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
            'journal_issue_id' => ['required', 'integer', 'exists:journal_issues,id'],
            'title' => ['required', 'string', 'max:500'],
            'author' => ['required', 'string', 'max:500'],
            'resource_field_id' => ['nullable', 'integer', 'exists:resource_fields,id'],
            'language_id' => ['nullable', 'integer', 'exists:languages,id'],
            'category' => ['nullable', new Enum(ArticleCategory::class)],
            'doi' => ['nullable', 'string', 'max:255'],
            'pages' => ['nullable', 'string', 'max:50'],
            'annotation' => ['nullable', 'string'],
            'electronic_file' => ['nullable', 'mimes:pdf', 'max:972800'], // 950 MB
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'journal_issue_id' => __('Jurnal soni'),
            'title' => __('Sarlavha'),
            'author' => __('Muallif(lar)'),
            'resource_field_id' => __('Resurs sohasi'),
            'language_id' => __('Tili'),
            'category' => __('Kategoriyasi'),
            'doi' => __('DOI'),
            'pages' => __('Sahifalar'),
            'annotation' => __('Annotatsiya'),
            'electronic_file' => __('Elektron fayl'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'journal_issue_id.required' => __('Jurnal sonini tanlang.'),
            'journal_issue_id.exists' => __('Tanlangan jurnal soni topilmadi.'),
        ];
    }
}
