<?php

namespace Database\Factories;

use App\Models\NewsCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsCategory>
 */
class NewsCategoryFactory extends Factory
{
    protected $model = NewsCategory::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->word();

        return [
            'name' => ['uz' => $name, 'ru' => $name, 'kk' => $name],
        ];
    }
}
