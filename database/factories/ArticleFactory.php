<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\JournalIssue;
use App\Models\ResourceField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        return [
            'journal_issue_id' => JournalIssue::factory(),
            'title' => $this->faker->unique()->sentence(5),
            'author' => $this->faker->name(),
            'resource_field_id' => ResourceField::factory(),
            'pages' => $this->faker->numberBetween(1, 20).'-'.$this->faker->numberBetween(21, 40),
            'annotation' => $this->faker->paragraph(),
            // slug is set by the observer
        ];
    }

    public function withPdf(string $path = 'articles/electronic/test.pdf'): static
    {
        return $this->state(fn () => ['electronic_file' => $path]);
    }
}
