<?php

namespace App\Data;

use Illuminate\Http\Request;

/**
 * DTO for passing data from Controller → Service (subscription catalog entry).
 */
class SubscriptionCatalogData
{
    public function __construct(
        public readonly int $year,
        public readonly int $journal_id,
        public readonly float $annual_price,
        public readonly bool $is_selected,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            year: $request->integer('year'),
            journal_id: $request->integer('journal_id'),
            annual_price: (float) $request->input('annual_price'),
            is_selected: $request->boolean('is_selected'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'year' => $this->year,
            'journal_id' => $this->journal_id,
            'annual_price' => $this->annual_price,
            'is_selected' => $this->is_selected,
        ];
    }
}
