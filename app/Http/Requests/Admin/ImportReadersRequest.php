<?php

namespace App\Http\Requests\Admin;

use App\Services\ServerLimitsService;
use Illuminate\Foundation\Http\FormRequest;

class ImportReadersRequest extends FormRequest
{
    /**
     * Ceiling this form will never exceed regardless of how much the server
     * allows. A reader list, even with a photo per row, has no business being
     * larger than this — and an unbounded rule on a host with no upload limit
     * would be worse than the limit it replaced.
     */
    private const APP_MAX_KILOBYTES = 102400; // 100 MB

    public function authorize(): bool
    {
        // Route is under `auth` middleware. When roles are added — ReaderPolicy.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:'.$this->maxKilobytes(),
            ],
        ];
    }

    /**
     * The smaller of what this form allows and what the server will physically
     * accept.
     *
     * Promising more than the server takes is not a harmless overestimate: PHP
     * discards an oversized request before Laravel runs, so the librarian gets
     * a bare 503 from the web server with nothing in any log, instead of the
     * message below telling them the file is too big. That is exactly how the
     * reader import failed on production, where upload_max_filesize was 2M
     * while this rule advertised 100M.
     */
    private function maxKilobytes(): int
    {
        $serverBytes = ServerLimitsService::effectiveUploadBytes();

        if ($serverBytes <= 0) {
            return self::APP_MAX_KILOBYTES; // unlimited server — the app's own cap stands
        }

        return min(self::APP_MAX_KILOBYTES, intdiv($serverBytes, 1024));
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'file' => __('Excel fayl'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => __('Excel faylni tanlang.'),
            'file.mimes' => __('Fayl formati .xlsx yoki .xls bo‘lishi kerak.'),
            // Stated in MB, and from the same number the rule uses, so the
            // message can never quote a limit that isn't the one enforced.
            'file.max' => __('Fayl hajmi :n MB dan oshmasligi kerak.', [
                'n' => (int) round($this->maxKilobytes() / 1024),
            ]),
        ];
    }
}
