<?php

namespace Database\Factories;

use App\Models\ContributorRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContributorRole>
 */
class ContributorRoleFactory extends Factory
{
    protected $model = ContributorRole::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->jobTitle(),
        ];
    }
}
