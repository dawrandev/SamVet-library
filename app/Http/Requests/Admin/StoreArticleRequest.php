<?php

namespace App\Http\Requests\Admin;

use App\Enums\ArticleCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreArticleRequest extends FormRequest
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
        $maxYear = (int) date('Y') + 1;

        return [
            // Either a library-held journal issue, or a free-text external
            // journal name (e.g. an international journal the library
            // doesn't hold) — exactly one of the two is required.
            'journal_issue_id' => ['nullable', 'required_without:external_journal_name', 'integer', 'exists:journal_issues,id'],
            'external_journal_name' => ['nullable', 'required_without:journal_issue_id', 'string', 'max:255'],
            'external_journal_year' => ['nullable', 'integer', 'min:1000', "max:{$maxYear}"],
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
            'external_journal_name' => __('Jurnal nomi'),
            'external_journal_year' => __('Nashr yili'),
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
            'journal_issue_id.required_without' => __('Kutubxonadagi jurnalning sonini tanlang, yoki tashqi jurnal nomini yozing.'),
            'journal_issue_id.exists' => __('Tanlangan jurnal soni topilmadi.'),
            'external_journal_name.required_without' => __('Kutubxonadagi jurnalning sonini tanlang, yoki tashqi jurnal nomini yozing.'),
        ];
    }
}
