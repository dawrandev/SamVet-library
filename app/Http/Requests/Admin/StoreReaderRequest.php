<?php

namespace App\Http\Requests\Admin;

use App\Enums\Gender;
use App\Enums\ReaderStatus;
use App\Enums\ReaderType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Unique;

class StoreReaderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Marshrut `auth` middleware ostida. Rollar qo'shilsa — ReaderPolicy.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'type' => ['required', new Enum(ReaderType::class)],
            'status' => ['required', new Enum(ReaderStatus::class)],

            'id_number' => ['nullable', 'string', 'max:255', $this->idNumberUnique()],
            'registration_number' => ['nullable', 'string', 'max:255'],

            'affiliation_place' => ['nullable', 'string', 'max:255'],
            'affiliation_unit' => ['nullable', 'string', 'max:255'],
            'affiliation_group' => ['nullable', 'string', 'max:255'],

            'nationality' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'passport' => ['nullable', 'string', 'max:255'],
            'pinfl' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', new Enum(Gender::class)],
            'district' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'member_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],

            'issued_date' => ['nullable', 'date'],
            'other_library_member' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],

            'photo' => ['nullable', 'image', 'max:2048'], // 2 MB
        ];
    }

    /**
     * ID raqami takrorlanmasligi. Update'da joriy a'zo ignore qilinadi (override).
     */
    protected function idNumberUnique(): Unique
    {
        return Rule::unique('readers', 'id_number');
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'full_name' => __('F.I.SH'),
            'type' => __('Turi'),
            'status' => __('Holati'),
            'id_number' => __('ID raqami'),
            'registration_number' => __('Ro‘yxat raqami'),
            'affiliation_place' => __('Mansublik joyi'),
            'affiliation_unit' => __('Mansublik bo‘limi'),
            'affiliation_group' => __('Mansublik guruhi'),
            'nationality' => __('Millati'),
            'birth_date' => __('Tug‘ilgan sana'),
            'passport' => __('Passport'),
            'pinfl' => __('JSHSHIR (PINFL)'),
            'gender' => __('Jinsi'),
            'district' => __('Tuman'),
            'address' => __('Manzil'),
            'phone' => __('Telefon'),
            'member_year' => __('A‘zolik yili'),
            'issued_date' => __('Berilgan sana'),
            'other_library_member' => __('Boshqa kutubxona a‘zosi'),
            'note' => __('Izoh'),
            'photo' => __('Rasm'),
        ];
    }
}
