<?php

namespace Database\Factories;

use App\Models\Journal;
use App\Models\SubscriptionCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionCatalog>
 */
class SubscriptionCatalogFactory extends Factory
{
    protected $model = SubscriptionCatalog::class;

    public function definition(): array
    {
        return [
            'year' => 2027,
            'journal_id' => Journal::factory(),
            'annual_price' => $this->faker->numberBetween(300000, 3000000),
            'is_selected' => true,
        ];
    }
}
