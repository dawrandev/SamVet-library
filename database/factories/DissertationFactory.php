<?php

namespace Database\Factories;

use App\Models\Dissertation;
use App\Models\ResourceField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dissertation>
 */
class DissertationFactory extends Factory
{
    protected $model = Dissertation::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->unique()->sentence(5),
            'author' => $this->faker->name(),
            'resource_field_id' => ResourceField::factory(),
            'annotation' => $this->faker->paragraph(),
            // slug is set by the observer
        ];
    }
}
