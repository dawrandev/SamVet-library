<?php

namespace App\Data;

use Illuminate\Http\Request;

/**
 * DTO for passing reader-type lookup data from Controller → Service.
 * A dedicated DTO (not the shared LookupData) since this lookup carries two
 * extra fields beyond a plain name.
 */
class ReaderTypeData
{
    public function __construct(
        public readonly string $name,
        public readonly bool $is_student,
        public readonly string $certificate_color,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: trim((string) $request->input('name')),
            is_student: $request->boolean('is_student'),
            certificate_color: $request->string('certificate_color')->toString(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'name' => $this->name,
            'is_student' => $this->is_student,
            'certificate_color' => $this->certificate_color,
        ];
    }
}
