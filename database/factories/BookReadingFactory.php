<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\BookReading;
use App\Models\Reader;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookReading>
 */
class BookReadingFactory extends Factory
{
    protected $model = BookReading::class;

    public function definition(): array
    {
        return [
            'reader_id' => Reader::factory(),
            'book_id' => Book::factory(),
            'read_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
