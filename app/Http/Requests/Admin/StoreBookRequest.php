<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Marshrut `auth` middleware ostida. Rollar qo'shilса — BookPolicy.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxYear = (int) date('Y') + 1;

        return [
            'title' => ['required', 'string', 'max:255'],
            'udc' => ['nullable', 'string', 'max:50'],
            'author_mark' => ['nullable', 'string', 'max:50'],
            'isbn' => ['nullable', 'string', 'max:30'],
            'book_type_id' => ['nullable', 'exists:book_types,id'],
            'language_id' => ['nullable', 'exists:languages,id'],
            'publisher_id' => ['nullable', 'exists:publishers,id'],
            'publication_year' => ['nullable', 'integer', 'min:1000', "max:{$maxYear}"],
            'publication_place' => ['nullable', 'array'],
            'publication_place.uz' => ['nullable', 'string', 'max:255'],
            'publication_place.ru' => ['nullable', 'string', 'max:255'],
            'publication_place.kk' => ['nullable', 'string', 'max:255'],
            'pages' => ['nullable', 'integer', 'min:1'],
            'print_run' => ['nullable', 'integer', 'min:1'],
            'annotation' => ['nullable', 'string'],

            'translation_of' => ['nullable', 'integer', 'exists:books,id'],

            'author_ids' => ['array'],
            'author_ids.*' => ['integer', 'exists:authors,id'],
            'category_ids' => ['array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],

            'cover' => ['nullable', 'image', 'max:2048'],                 // 2 MB
            'electronic_file' => ['nullable', 'mimes:pdf', 'max:51200'],  // 50 MB
            'audio_file' => ['nullable', 'mimes:mp3,mpga,wav', 'max:102400'], // 100 MB
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => __('Sarlavha'),
            'udc' => __('UO‘K'),
            'author_mark' => __('Avtorlik belgi'),
            'isbn' => __('ISBN'),
            'book_type_id' => __('Turi'),
            'language_id' => __('Tili'),
            'publisher_id' => __('Nashriyoti'),
            'publication_year' => __('Nashr yili'),
            'pages' => __('Sahifalar soni'),
            'print_run' => __('Tiraj'),
            'annotation' => __('Annotatsiya'),
            'cover' => __('Muqova rasmi'),
            'electronic_file' => __('Elektron fayl'),
            'audio_file' => __('Audio fayl'),
        ];
    }
}
