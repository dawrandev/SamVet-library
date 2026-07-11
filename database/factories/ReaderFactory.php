<?php

namespace Database\Factories;

use App\Enums\ReaderStatus;
use App\Enums\ReaderType;
use App\Models\Reader;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reader>
 */
class ReaderFactory extends Factory
{
    protected $model = Reader::class;

    public function definition(): array
    {
        // The observer stamps the shared password on create; tests that need a
        // specific one can override `password`.
        return [
            'id_number' => strtoupper($this->faker->unique()->bothify('??######')),
            'full_name' => $this->faker->name(),
            'type' => ReaderType::Bachelor->value,
            'status' => ReaderStatus::Active->value,
        ];
    }

    public function blocked(): static
    {
        return $this->state(fn () => [
            'status' => ReaderStatus::Blocked->value,
        ]);
    }
}
