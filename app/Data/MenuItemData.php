<?php

namespace App\Data;

use App\Enums\MenuItemType;
use Illuminate\Http\Request;

/**
 * DTO for passing data from Controller → Service.
 * A typed object instead of an array (`$data['x']`).
 */
class MenuItemData
{
    public function __construct(
        /** @var array<string, string> Title (translation: uz/ru/kk) */
        public readonly array $title,
        public readonly ?string $url,
        public readonly MenuItemType $type,
        public readonly ?int $parent_id,
        public readonly ?int $sort_order,
        public readonly bool $is_active,
        public readonly bool $target_blank,
    ) {}

    public static function fromRequest(Request $request): self
    {
        // Title: {uz,ru,kk} — empty values are dropped (uz is required, validated by FormRequest)
        $title = array_filter(
            array_map('trim', (array) $request->input('title', [])),
            static fn (string $v): bool => $v !== '',
        );

        return new self(
            title: $title,
            url: $request->input('url') ?: null,
            type: MenuItemType::from((string) $request->input('type', MenuItemType::Dropdown->value)),
            parent_id: $request->integer('parent_id') ?: null,
            sort_order: $request->filled('sort_order') ? $request->integer('sort_order') : null,
            is_active: $request->boolean('is_active'),
            target_blank: $request->boolean('target_blank'),
        );
    }

    /**
     * Fields written to the menu_items table.
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'title' => $this->title,
            'url' => $this->url,
            'type' => $this->type,
            'parent_id' => $this->parent_id,
            'sort_order' => $this->sort_order ?? 0,
            'is_active' => $this->is_active,
            'target_blank' => $this->target_blank,
        ];
    }
}
