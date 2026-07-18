<?php

namespace Database\Factories;

use App\Models\ScienceField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScienceField>
 */
class ScienceFieldFactory extends Factory
{
    protected $model = ScienceField::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true).' fanlari',
        ];
    }
}
