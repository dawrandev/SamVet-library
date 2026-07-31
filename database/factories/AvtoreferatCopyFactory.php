<?php

namespace Database\Factories;

use App\Enums\CopyCondition;
use App\Models\Avtoreferat;
use App\Models\AvtoreferatCopy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AvtoreferatCopy>
 */
class AvtoreferatCopyFactory extends Factory
{
    protected $model = AvtoreferatCopy::class;

    public function definition(): array
    {
        return [
            'avtoreferat_id' => Avtoreferat::factory(),
            'inventory_number' => strtoupper($this->faker->unique()->bothify('AVT-#####')),
            'condition' => [$this->faker->randomElement(CopyCondition::cases())->value],
        ];
    }
}
