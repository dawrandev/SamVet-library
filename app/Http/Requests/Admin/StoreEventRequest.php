<?php

namespace App\Http\Requests\Admin;

use App\Enums\EventRole;
use App\Enums\EventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreEventRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', new Enum(EventType::class)],
            'date' => ['required', 'date'],
            'news_id' => ['nullable', 'integer', 'exists:news,id'],
            'note' => ['nullable', 'string', 'max:2000'],

            'location_ids' => ['nullable', 'array'],
            'location_ids.*' => ['integer', 'exists:event_locations,id'],

            'participants' => ['nullable', 'array'],
            'participants.*.is_external' => ['required', 'boolean'],
            // Same-index wildcard: only required when that same row is a reader row / an external row.
            'participants.*.reader_id' => ['nullable', 'integer', 'exists:readers,id', 'required_if:participants.*.is_external,0'],
            'participants.*.external_name' => ['nullable', 'string', 'max:255', 'required_if:participants.*.is_external,1'],
            'participants.*.role' => ['required', new Enum(EventRole::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('Nomi'),
            'type' => __('Turi'),
            'date' => __('Sanasi'),
            'news_id' => __('Yangilik'),
            'note' => __('Izoh'),
            'location_ids' => __('O‘tkazilgan joyi'),
            'participants.*.reader_id' => __('Ishtirokchi'),
            'participants.*.external_name' => __('Ishtirokchi ismi'),
            'participants.*.role' => __('Ishtirok maqsadi'),
        ];
    }
}
