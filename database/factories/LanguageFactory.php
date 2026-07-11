<?php

namespace Database\Factories;

use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Language>
 */
class LanguageFactory extends Factory
{
    protected $model = Language::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->languageCode();

        return [
            'name' => ['uz' => $name, 'ru' => $name, 'kk' => $name],
            'locale' => null,
        ];
    }

    public function locale(string $locale): static
    {
        return $this->state(fn () => ['locale' => $locale]);
    }
}
