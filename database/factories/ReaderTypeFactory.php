<?php

namespace Database\Factories;

use App\Models\ReaderType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReaderType>
 */
class ReaderTypeFactory extends Factory
{
    protected $model = ReaderType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'is_student' => true,
            'certificate_color' => '#2563eb',
        ];
    }
}
