<?php

namespace App\Http\Requests\Admin;

use App\Enums\ChunkedUploadKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StartChunkedUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is under 'auth' middleware. If roles are added — Policy.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'filename' => ['required', 'string', 'max:255'],
            'total_size' => ['required', 'integer', 'min:1'],
            'kind' => ['required', Rule::enum(ChunkedUploadKind::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $kind = ChunkedUploadKind::tryFrom($this->string('kind')->toString());
            $totalSize = $this->integer('total_size');

            if ($kind && $totalSize > $kind->maxKilobytes() * 1024) {
                $validator->errors()->add('total_size', __('Fayl hajmi ruxsat etilgan chegaradan katta.'));
            }
        });
    }

    public function kind(): ChunkedUploadKind
    {
        return ChunkedUploadKind::from($this->string('kind')->toString());
    }
}
