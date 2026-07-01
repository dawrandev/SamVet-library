<?php

namespace App\Http\Requests\Admin\Lookups;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Tarjimali lookup'lar (book_type, language, location) uchun validatsiya.
 * Boshqaruv panelida 3 til ham MAJBURIY (to'liq tarjima talab qilinadi).
 */
class TranslatableLookupRequest extends FormRequest
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
        return [
            'name' => ['required', 'array'],
            'name.uz' => ['required', 'string', 'max:255'],
            'name.ru' => ['required', 'string', 'max:255'],
            'name.kk' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name.uz' => __('Nomi (o‘zbekcha)'),
            'name.ru' => __('Nomi (ruscha)'),
            'name.kk' => __('Nomi (qoraqalpoqcha)'),
        ];
    }
}
