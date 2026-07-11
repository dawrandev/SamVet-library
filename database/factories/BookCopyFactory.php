<?php

namespace Database\Factories;

use App\Enums\BookFormat;
use App\Enums\CopyCondition;
use App\Enums\CopyStatus;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookCopy>
 */
class BookCopyFactory extends Factory
{
    protected $model = BookCopy::class;

    public function definition(): array
    {
        return [
            'book_id' => Book::factory(),
            'inventory_number' => strtoupper($this->faker->unique()->bothify('INV-#####')),
            'format' => BookFormat::Print->value,
            'condition' => CopyCondition::New->value,
            'status' => CopyStatus::Available->value,
            'location_id' => Location::factory(),
            'price' => $this->faker->numberBetween(10000, 100000),
        ];
    }

    public function borrowed(): static
    {
        return $this->state(fn () => ['status' => CopyStatus::Borrowed->value]);
    }
}
