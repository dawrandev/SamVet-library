<?php

namespace Database\Factories;

use App\Models\Avtoreferat;
use App\Models\JournalIssue;
use App\Models\ResourceField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Avtoreferat>
 */
class AvtoreferatFactory extends Factory
{
    protected $model = Avtoreferat::class;

    public function definition(): array
    {
        return [
            'journal_issue_id' => JournalIssue::factory(),
            'title' => $this->faker->unique()->sentence(5),
            'author' => $this->faker->name(),
            'resource_field_id' => ResourceField::factory(),
            'annotation' => $this->faker->paragraph(),
            // slug is set by the observer
        ];
    }
}
