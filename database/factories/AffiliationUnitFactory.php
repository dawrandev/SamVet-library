<?php

namespace Database\Factories;

use App\Models\AffiliationUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AffiliationUnit>
 */
class AffiliationUnitFactory extends Factory
{
    protected $model = AffiliationUnit::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->jobTitle(),
        ];
    }
}
