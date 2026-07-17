<?php

namespace App\Data;

use Illuminate\Http\Request;

/**
 * DTO for passing data from Controller → Service (event).
 */
class EventData
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly string $date,
        public readonly ?int $news_id,
        public readonly ?string $note,
        /** @var int[] */
        public readonly array $location_ids,
        /** @var array<int, array{is_external: mixed, reader_id: mixed, external_name: mixed, role: mixed}> */
        public readonly array $participants,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->string('name')->toString(),
            type: $request->string('type')->toString(),
            date: $request->string('date')->toString(),
            news_id: $request->integer('news_id') ?: null,
            note: $request->input('note') ?: null,
            location_ids: array_map('intval', $request->input('location_ids', [])),
            participants: $request->input('participants', []),
        );
    }

    /**
     * Only the scalar fields written to the events table.
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'date' => $this->date,
            'news_id' => $this->news_id,
            'note' => $this->note,
        ];
    }
}
