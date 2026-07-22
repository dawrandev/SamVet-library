<?php

namespace Database\Factories;

use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Video>
 */
class VideoFactory extends Factory
{
    protected $model = Video::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->sentence(3),
            'author' => $this->faker->name(),
            'annotation' => $this->faker->paragraph(),
            // slug is set by the observer
        ];
    }
}
