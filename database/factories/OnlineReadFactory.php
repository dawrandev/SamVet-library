<?php

namespace Database\Factories;

use App\Models\Audiobook;
use App\Models\Avtoreferat;
use App\Models\Book;
use App\Models\Dissertation;
use App\Models\OnlineRead;
use App\Models\Reader;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OnlineRead>
 */
class OnlineReadFactory extends Factory
{
    protected $model = OnlineRead::class;

    public function definition(): array
    {
        return [
            'reader_id' => Reader::factory(),
            'readable_type' => 'book',
            'readable_id' => Book::factory(),
            'read_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function video(): static
    {
        return $this->state(fn () => [
            'readable_type' => 'video',
            'readable_id' => Video::factory(),
        ]);
    }

    public function audiobook(): static
    {
        return $this->state(fn () => [
            'readable_type' => 'audiobook',
            'readable_id' => Audiobook::factory(),
        ]);
    }

    public function dissertation(): static
    {
        return $this->state(fn () => [
            'readable_type' => 'dissertation',
            'readable_id' => Dissertation::factory(),
        ]);
    }

    public function avtoreferat(): static
    {
        return $this->state(fn () => [
            'readable_type' => 'avtoreferat',
            'readable_id' => Avtoreferat::factory(),
        ]);
    }
}
