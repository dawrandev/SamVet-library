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
        return [
            'journal_id' => Journal::factory(),
            'year' => $this->faker->numberBetween(2015, 2025),
            'issue_number' => $this->faker->numberBetween(1, 12).'-son',
            'pages' => $this->faker->numberBetween(40, 160),
        ];
    }
}
