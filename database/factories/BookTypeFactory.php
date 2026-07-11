<?php

namespace Database\Factories;

use App\Models\BookType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookType>
 */
class BookTypeFactory extends Factory
{
    protected $model = BookType::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->word();

        return [
            'name' => ['uz' => $name, 'ru' => $name, 'kk' => $name],
        ];
    }
}
