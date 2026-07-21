<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\BookType;
use App\Models\Language;
use App\Models\PublicationPlace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(4);

        return [
            'title' => $title,
            'book_type_id' => BookType::factory(),
            'language_id' => Language::factory(),
            'publisher' => $this->faker->company(),
            'publication_place_id' => PublicationPlace::factory(),
            'publication_year' => $this->faker->numberBetween(1990, 2025),
            'pages' => $this->faker->numberBetween(50, 600),
            'isbn' => $this->faker->isbn13(),
            'udc' => (string) $this->faker->randomFloat(2, 1, 900),
            'annotation' => $this->faker->paragraph(),
            'target_audience' => $this->faker->randomElement(['Talabalar uchun', 'Kattalar uchun', 'O‘quvchilar uchun']),
            'size_cm' => $this->faker->numberBetween(15, 30),
            'print_sheets' => (string) $this->faker->randomFloat(1, 5, 40),
            // slug is set by the observer
        ];
    }

    /** A book that can be read online (has a stored PDF path). */
    public function withPdf(string $path = 'books/electronic/test.pdf'): static
    {
        return $this->state(fn () => ['electronic_file' => $path]);
    }
}
