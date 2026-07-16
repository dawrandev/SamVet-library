<?php

namespace App\Http\Requests\Admin;

use App\Enums\ComputerLocation;
use App\Enums\ComputerStatus;
use App\Enums\ComputerType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreComputerRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is under `auth` middleware. If roles are added — ComputerPolicy.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'model' => ['required', 'string', 'max:255'],
            'type' => ['required', new Enum(ComputerType::class)],
            'inventory_number' => ['required', 'string', 'max:100', $this->inventoryNumberUniqueRule()],
            'computer_number' => ['nullable', 'string', 'max:100', $this->computerNumberUniqueRule()],
            'status' => ['required', new Enum(ComputerStatus::class)],
            'location' => ['nullable', new Enum(ComputerLocation::class)],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Inventory number is unique (unrestricted on create).
     */
    protected function inventoryNumberUniqueRule(): object
    {
        return Rule::unique('computers', 'inventory_number');
    }

    /**
     * The number readers actually check the computer out by (unrestricted on create).
     */
    protected function computerNumberUniqueRule(): object
    {
        return Rule::unique('computers', 'computer_number');
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'model' => __('Modeli'),
            'type' => __('Turi'),
            'inventory_number' => __('Inventar raqami'),
            'computer_number' => __('Kompyuter raqami'),
            'status' => __('Holati'),
            'location' => __('Joylashuv'),
            'note' => __('Eslatma'),
        ];
    }
}
