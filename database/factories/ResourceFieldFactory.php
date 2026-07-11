<?php

namespace Database\Factories;

use App\Models\ResourceField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResourceField>
 */
class ResourceFieldFactory extends Factory
{
    protected $model = ResourceField::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->word();

        return [
            'name' => ['uz' => $name, 'ru' => $name, 'kk' => $name],
        ];
    }
}
