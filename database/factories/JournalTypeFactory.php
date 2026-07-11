<?php

namespace Database\Factories;

use App\Models\JournalType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalType>
 */
class JournalTypeFactory extends Factory
{
    protected $model = JournalType::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'name' => ['uz' => $name, 'ru' => $name, 'kk' => $name],
        ];
    }
}
