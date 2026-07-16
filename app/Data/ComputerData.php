<?php

namespace App\Data;

use Illuminate\Http\Request;

/**
 * DTO for passing data from Controller → Service (computer).
 * A typed object instead of an array (`$data['x']`).
 */
class ComputerData
{
    public function __construct(
        public readonly string $model,
        public readonly string $type,
        public readonly string $inventory_number,
        public readonly ?string $computer_number,
        public readonly string $status,
        public readonly ?string $location,
        public readonly ?string $note,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            model: $request->string('model')->toString(),
            type: $request->string('type')->toString(),
            inventory_number: $request->string('inventory_number')->toString(),
            computer_number: $request->input('computer_number') ?: null,
            status: $request->string('status')->toString(),
            location: $request->input('location') ?: null,
            note: $request->input('note'),
        );
    }

    /**
     * Attributes written to the computers table.
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'model' => $this->model,
            'type' => $this->type,
            'inventory_number' => $this->inventory_number,
            'computer_number' => $this->computer_number,
            'status' => $this->status,
            'location' => $this->location,
            'note' => $this->note,
        ];
    }
}
