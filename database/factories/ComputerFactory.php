<?php

namespace Database\Factories;

use App\Enums\ComputerLocation;
use App\Enums\ComputerStatus;
use App\Enums\ComputerType;
use App\Models\Computer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Computer>
 */
class ComputerFactory extends Factory
{
    protected $model = Computer::class;

    public function definition(): array
    {
        return [
            'model' => $this->faker->words(3, true),
            'type' => $this->faker->randomElement(ComputerType::cases())->value,
            'inventory_number' => strtoupper($this->faker->unique()->bothify('KMP-#####')),
            'status' => $this->faker->randomElement(ComputerStatus::cases())->value,
            'location' => $this->faker->randomElement(ComputerLocation::cases())->value,
            'note' => null,
        ];
    }
}
