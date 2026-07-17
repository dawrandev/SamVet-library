<?php

namespace Database\Factories;

use App\Enums\CopyCondition;
use App\Enums\CopyStatus;
use App\Models\JournalCopy;
use App\Models\JournalIssue;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalCopy>
 */
class JournalCopyFactory extends Factory
{
    protected $model = JournalCopy::class;

    public function definition(): array
    {
        return [
            'journal_issue_id' => JournalIssue::factory(),
            'inventory_number' => strtoupper($this->faker->unique()->bothify('JINV-#####')),
            'condition' => CopyCondition::New->value,
            'status' => CopyStatus::Available->value,
            'location_id' => Location::factory(),
            'arrival_date' => $this->faker->dateTimeBetween('-1 year')->format('Y-m-d'),
        ];
    }

    public function borrowed(): static
    {
        return $this->state(fn () => ['status' => CopyStatus::Borrowed->value]);
    }
}
