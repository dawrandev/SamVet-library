<?php

namespace App\Data;

use Illuminate\Http\Request;

/**
 * DTO for passing data from Controller → Service (avtoreferat copy).
 * A typed object instead of an array (`$data['x']`).
 */
class AvtoreferatCopyData
{
    public function __construct(
        public readonly ?string $inventory_number,
        /** @var array<int, string> */
        public readonly array $condition,
        public readonly ?string $acquisition_act_number,
        public readonly ?string $acquisition_act_at,
        public readonly ?string $disposal_act_number,
        public readonly ?string $disposal_act_at,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            inventory_number: $request->input('inventory_number') ?: null,
            condition: $request->input('condition', []),
            acquisition_act_number: $request->input('acquisition_act_number') ?: null,
            acquisition_act_at: $request->input('acquisition_act_at') ?: null,
            disposal_act_number: $request->input('disposal_act_number') ?: null,
            disposal_act_at: $request->input('disposal_act_at') ?: null,
        );
    }

    /**
     * Only the scalar fields written to the avtoreferat_copies table.
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'inventory_number' => $this->inventory_number,
            'condition' => $this->condition,
            'acquisition_act_number' => $this->acquisition_act_number,
            'acquisition_act_at' => $this->acquisition_act_at,
            'disposal_act_number' => $this->disposal_act_number,
            'disposal_act_at' => $this->disposal_act_at,
        ];
    }
}
