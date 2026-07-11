<?php

namespace Database\Factories;

use App\Models\News;
use App\Models\NewsCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<News>
 */
class NewsFactory extends Factory
{
    protected $model = News::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(6);
        $excerpt = $this->faker->sentence();
        $body = $this->faker->paragraphs(3, true);

        return [
            'news_category_id' => NewsCategory::factory(),
            'title' => ['uz' => $title, 'ru' => $title, 'kk' => $title],
            'excerpt' => ['uz' => $excerpt, 'ru' => $excerpt, 'kk' => $excerpt],
            'body' => ['uz' => "<p>{$body}</p>", 'ru' => "<p>{$body}</p>", 'kk' => "<p>{$body}</p>"],
            'published_at' => now()->subDay(),
            // slug is set by the observer
        ];
    }

    /** Draft — not yet published, so hidden from the public feed. */
    public function draft(): static
    {
        return $this->state(fn () => ['published_at' => null]);
    }
}
