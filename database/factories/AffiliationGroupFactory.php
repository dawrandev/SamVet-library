<?php

namespace Database\Factories;

use App\Models\AffiliationGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AffiliationGroup>
 */
class AffiliationGroupFactory extends Factory
{
    protected $model = AffiliationGroup::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->bothify('##-##'),
        ];
    }
}
