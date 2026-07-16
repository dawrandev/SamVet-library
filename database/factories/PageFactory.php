<?php

namespace Database\Factories;

use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'menu_item_id' => MenuItem::factory(),
            'title' => ['uz' => $title],
            'body' => ['uz' => '<p>'.fake()->paragraph().'</p>'],
            'cover_image' => null,
        ];
    }
}
