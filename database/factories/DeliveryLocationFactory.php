<?php

namespace Database\Factories;

use App\Models\DeliveryLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryLocation>
 */
class DeliveryLocationFactory extends Factory
{
    protected $model = DeliveryLocation::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company(),
        ];
    }
}
