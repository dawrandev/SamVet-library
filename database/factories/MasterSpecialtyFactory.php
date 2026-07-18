<?php

namespace Database\Factories;

use App\Models\MasterSpecialty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MasterSpecialty>
 */
class MasterSpecialtyFactory extends Factory
{
    protected $model = MasterSpecialty::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->numerify('########').' - '.$this->faker->word(),
        ];
    }
}
