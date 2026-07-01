<?php

namespace App\Data;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * Controller → Service ma'lumot uzatish uchun DTO (kitob nusxasi).
 * Massiv (`$data['x']`) o'rniga tipli obyekt.
 */
class CopyData
{
    public function __construct(
        public readonly string $inventory_number,
        public readonly string $format,
        public readonly string $condition,
        public readonly string $status,
        public readonly ?int $location_id,
        public readonly ?float $price,
        public readonly ?UploadedFile $acquisition_act,
        public readonly ?UploadedFile $disposal_act,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            inventory_number: $request->string('inventory_number')->toString(),
            format: $request->string('format')->toString(),
            condition: $request->string('condition')->toString(),
            status: $request->string('status')->toString(),
            location_id: $request->integer('location_id') ?: null,
            price: $request->filled('price') ? (float) $request->input('price') : null,
            acquisition_act: $request->file('acquisition_act'),
            disposal_act: $request->file('disposal_act'),
        );
    }

    /**
     * Faqat book_copies jadvaliga yoziladigan skalyar maydonlar (faylsiz).
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'inventory_number' => $this->inventory_number,
            'format' => $this->format,
            'condition' => $this->condition,
            'status' => $this->status,
            'location_id' => $this->location_id,
            'price' => $this->price,
        ];
    }
}
