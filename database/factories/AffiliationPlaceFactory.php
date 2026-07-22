<?php

namespace Database\Factories;

use App\Models\AffiliationPlace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AffiliationPlace>
 */
class AffiliationPlaceFactory extends Factory
{
    protected $model = AffiliationPlace::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company(),
        ];
    }
}
