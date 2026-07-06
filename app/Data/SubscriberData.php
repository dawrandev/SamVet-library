<?php

namespace App\Data;

use Illuminate\Http\Request;

/**
 * DTO for passing data from Controller → Service (subscriber).
 */
class SubscriberData
{
    public function __construct(
        public readonly string $full_name,
        public readonly ?string $position,
        public readonly ?string $department,
        public readonly ?string $phone,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            full_name: $request->string('full_name')->toString(),
            position: $request->input('position'),
            department: $request->input('department'),
            phone: $request->input('phone'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'full_name' => $this->full_name,
            'position' => $this->position,
            'department' => $this->department,
            'phone' => $this->phone,
        ];
    }
}
