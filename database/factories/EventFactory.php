<?php

namespace Database\Factories;

use App\Enums\EventType;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'type' => $this->faker->randomElement(EventType::cases())->value,
            'date' => $this->faker->dateTimeBetween('-1 year')->format('Y-m-d'),
        ];
    }
}
