<?php

namespace App\Data;

use Illuminate\Http\Request;

/**
 * Controller → Service ma'lumot uzatish uchun DTO (jurnal nusxasi).
 */
class JournalCopyData
{
    public function __construct(
        public readonly string $inventory_number,
        public readonly ?string $condition,
        public readonly string $status,
        public readonly ?int $location_id,
        public readonly ?string $arrival_date,
        public readonly ?float $price,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            inventory_number: $request->string('inventory_number')->toString(),
            condition: $request->input('condition') ?: null,
            status: $request->string('status')->toString(),
            location_id: $request->integer('location_id') ?: null,
            arrival_date: $request->input('arrival_date') ?: null,
            price: $request->filled('price') ? (float) $request->input('price') : null,
        );
    }

    /**
     * Faqat journal_copies jadvaliga yoziladigan skalyar maydonlar.
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'inventory_number' => $this->inventory_number,
            'condition' => $this->condition,
            'status' => $this->status,
            'location_id' => $this->location_id,
            'arrival_date' => $this->arrival_date,
            'price' => $this->price,
        ];
    }
}
