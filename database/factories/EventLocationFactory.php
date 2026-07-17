<?php

namespace Database\Factories;

use App\Models\EventLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventLocation>
 */
class EventLocationFactory extends Factory
{
    protected $model = EventLocation::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
        ];
    }
}
