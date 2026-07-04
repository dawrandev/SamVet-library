<?php

namespace App\Data;

use Illuminate\Http\Request;

/**
 * Controller → Service ma'lumot uzatish uchun DTO.
 * Massiv (`$data['x']`) o'rniga tipli obyekt.
 */
class MenuItemData
{
    public function __construct(
        /** @var array<string, string> Sarlavha (tarjima: uz/ru/kk) */
        public readonly array $title,
        public readonly ?string $url,
        public readonly ?int $parent_id,
        public readonly ?int $sort_order,
        public readonly bool $is_active,
        public readonly bool $target_blank,
    ) {}

    public static function fromRequest(Request $request): self
    {
        // Sarlavha: {uz,ru,kk} — bo'sh qiymatlar tashlanadi (uz majburiy, FormRequest tekshiradi)
        $title = array_filter(
            array_map('trim', (array) $request->input('title', [])),
            static fn (string $v): bool => $v !== '',
        );

        return new self(
            title: $title,
            url: $request->input('url') ?: null,
            parent_id: $request->integer('parent_id') ?: null,
            sort_order: $request->filled('sort_order') ? $request->integer('sort_order') : null,
            is_active: $request->boolean('is_active'),
            target_blank: $request->boolean('target_blank'),
        );
    }

    /**
     * menu_items jadvaliga yoziladigan maydonlar.
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'title' => $this->title,
            'url' => $this->url,
            'parent_id' => $this->parent_id,
            'sort_order' => $this->sort_order ?? 0,
            'is_active' => $this->is_active,
            'target_blank' => $this->target_blank,
        ];
    }
}
