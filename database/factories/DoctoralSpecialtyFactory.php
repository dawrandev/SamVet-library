<?php

namespace Database\Factories;

use App\Models\DoctoralSpecialty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DoctoralSpecialty>
 */
class DoctoralSpecialtyFactory extends Factory
{
    protected $model = DoctoralSpecialty::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->numerify('##.##.##').'-'.$this->faker->word(),
        ];
    }
}
