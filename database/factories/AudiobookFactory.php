<?php

namespace Database\Factories;

use App\Models\Audiobook;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Audiobook>
 */
class AudiobookFactory extends Factory
{
    protected $model = Audiobook::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->sentence(3),
            'author' => $this->faker->name(),
            'annotation' => $this->faker->paragraph(),
            // slug is set by the observer
        ];
    }
}
