<?php

namespace Database\Factories;

use App\Enums\MenuItemType;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuItem>
 */
class MenuItemFactory extends Factory
{
    protected $model = MenuItem::class;

    public function definition(): array
    {
        $title = fake()->unique()->words(2, true);

        return [
            'parent_id' => null,
            'title' => ['uz' => $title, 'ru' => $title, 'kk' => $title],
            'url' => null,
            'type' => MenuItemType::Page,
            'sort_order' => 0,
            'is_active' => true,
            'target_blank' => false,
        ];
    }

    /** A child of the given menu item. */
    public function childOf(MenuItem $parent): static
    {
        return $this->state(fn () => ['parent_id' => $parent->id]);
    }

    /** A top-level dropdown container. */
    public function dropdown(): static
    {
        return $this->state(fn () => ['type' => MenuItemType::Dropdown]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
