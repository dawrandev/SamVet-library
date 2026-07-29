<?php

namespace Database\Factories;

use App\Models\DepartmentCoverage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DepartmentCoverage>
 */
class DepartmentCoverageFactory extends Factory
{
    protected $model = DepartmentCoverage::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true).' kafedrasi';

        return [
            'name' => ['uz' => $name, 'ru' => $name, 'kk' => $name],
            'percentage' => $this->faker->numberBetween(0, 100),
        ];
    }
}
