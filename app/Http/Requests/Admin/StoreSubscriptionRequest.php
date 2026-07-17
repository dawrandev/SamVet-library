<?php

namespace App\Http\Requests\Admin;

use App\Enums\Month;
use App\Enums\SubscriptionSource;
use App\Models\Subscription;
use App\Models\SubscriptionCatalog;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreSubscriptionRequest extends FormRequest
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
        $catalogDriven = $this->isCatalogDriven();

        return [
            'source' => ['required', new Enum(SubscriptionSource::class)],
            // Only required when funded by a reader — budget-funded subscriptions have none.
            'reader_id' => ['nullable', 'required_if:source,'.SubscriptionSource::Reader->value, 'exists:readers,id'],
            'journal_id' => ['required', 'exists:journals,id'],
            // Controlled destination — never a free-typed address — so issues stop being
            // mailed to a subscriber's home instead of the library/branch.
            'delivery_location_id' => ['required', 'exists:delivery_locations,id'],
            'post_branch_id' => ['nullable', 'exists:post_branches,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'start_month' => ['required', 'integer', new Enum(Month::class)],
            'end_month' => ['required', 'integer', new Enum(Month::class), 'gte:start_month'],
            // From CATALOG_ENFORCED_FROM_YEAR on, the amount is always computed from the
            // catalog's annual price (see withValidator/Service) — never trusted from the client.
            'amount' => [$catalogDriven ? 'nullable' : 'required', 'numeric', 'min:0'],
            'receipt_file' => ['nullable', 'mimes:jpg,jpeg,png,pdf', 'max:10240'], // 10 MB — payment receipt scan/photo
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->isCatalogDriven()) {
                return;
            }

            $this->validateJournalIsShortlisted($validator);
            $this->validateSequentialPeriod($validator);
        });
    }

    protected function isCatalogDriven(): bool
    {
        return $this->integer('year') >= Subscription::CATALOG_ENFORCED_FROM_YEAR;
    }

    /**
     * The journal must be part of that year's internal (shortlisted) catalog —
     * the whole point of the shortlist is that professors only pick from it.
     */
    protected function validateJournalIsShortlisted(Validator $validator): void
    {
        $year = $this->integer('year');
        $journalId = $this->integer('journal_id');

        if (! $journalId) {
            return; // already flagged by the 'required' rule
        }

        $inShortlist = SubscriptionCatalog::where('year', $year)
            ->where('journal_id', $journalId)
            ->where('is_selected', true)
            ->exists();

        if (! $inShortlist) {
            $validator->errors()->add('journal_id', __('Bu nashr :year yil ichki katalogida yo‘q.', ['year' => $year]));
        }
    }

    /**
     * Each new period for the same subscriber (reader, or "budget" as a whole)
     * + journal + year must start exactly where the previous one left off —
     * no gaps (skipping straight to April), no re-subscribing the same months.
     */
    protected function validateSequentialPeriod(Validator $validator): void
    {
        $year = $this->integer('year');
        $journalId = $this->integer('journal_id');
        $startMonth = $this->integer('start_month');

        if (! $journalId || ! $startMonth) {
            return; // already flagged by their own 'required' rules
        }

        $source = $this->string('source')->toString();
        $readerId = $source === SubscriptionSource::Reader->value ? $this->integer('reader_id') : null;

        if ($source === SubscriptionSource::Reader->value && ! $readerId) {
            return; // already flagged by reader_id's 'required_if' rule
        }

        $query = Subscription::query()
            ->where('year', $year)
            ->where('journal_id', $journalId)
            ->where('source', $source)
            ->when($readerId, fn ($q) => $q->where('reader_id', $readerId));

        if ($existingId = $this->route('subscription')?->id) {
            $query->where('id', '!=', $existingId);
        }

        $expectedStart = ((int) $query->max('end_month')) + 1;

        if ($startMonth !== $expectedStart) {
            $validator->errors()->add('start_month', $expectedStart > 12
                ? __('Bu nashrga :year yil uchun barcha oylar allaqachon obuna qilingan.', ['year' => $year])
                : __('Boshlanish oyi :month bo‘lishi kerak — obuna davrlari uzilishsiz, ketma-ket bo‘lishi shart.', ['month' => Month::from($expectedStart)->label()]));
        }
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'source' => __('Manba'),
            'reader_id' => __('Obunachi'),
            'journal_id' => __('Nashr'),
            'delivery_location_id' => __('Yetkazib berish manzili'),
            'post_branch_id' => __('Pochta filiali'),
            'year' => __('Yil'),
            'start_month' => __('Boshlanish oyi'),
            'end_month' => __('Tugash oyi'),
            'amount' => __('Summa'),
            'receipt_file' => __('Kvitansiya'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'source.required' => __('Obuna manbasini tanlang.'),
            'reader_id.required_if' => __('Obunachini tanlang.'),
            'journal_id.required' => __('Nashrni tanlang.'),
            'delivery_location_id.required' => __('Yetkazib berish manzilini tanlang.'),
            'end_month.gte' => __('Tugash oyi boshlanish oyidan oldin bo‘lmasligi kerak.'),
        ];
    }
}
