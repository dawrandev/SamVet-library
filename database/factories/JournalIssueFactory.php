<?php

namespace Database\Factories;

use App\Models\Journal;
use App\Models\JournalIssue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalIssue>
 */
class JournalIssueFactory extends Factory
{
    protected $model = JournalIssue::class;

    public function definition(): array
    {
        $year = $this->faker->numberBetween(2015, 2025);

        return [
            'journal_id' => Journal::factory(),
            'year' => $year,
            'issue_date' => $this->faker->dateTimeBetween("{$year}-01-01", "{$year}-12-31")->format('Y-m-d'),
            'issue_number' => $this->faker->numberBetween(1, 12).'-son',
            'pages' => $this->faker->numberBetween(40, 160),
        ];
    }
}
