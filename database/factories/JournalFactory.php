<?php

namespace Database\Factories;

use App\Enums\NewspaperType;
use App\Enums\PeriodicityUnit;
use App\Enums\PublicationKind;
use App\Models\Journal;
use App\Models\JournalType;
use App\Models\Language;
use App\Models\PublicationPlace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Journal>
 */
class JournalFactory extends Factory
{
    protected $model = Journal::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->sentence(3),
            'kind' => PublicationKind::Journal->value,
            'journal_type_id' => JournalType::factory(),
            'founder' => $this->faker->company(),
            'language_id' => Language::factory(),
            'publication_place_id' => PublicationPlace::factory(),
            'issn' => $this->faker->numerify('####-####'),
            'periodicity_unit' => PeriodicityUnit::Month->value,
            'periodicity_interval' => 1,
            'periodicity_count' => 1,
            // slug is set by the observer
        ];
    }

    public function newspaper(): static
    {
        return $this->state(fn () => [
            'kind' => PublicationKind::Newspaper->value,
            'newspaper_type' => NewspaperType::Pedagogical->value,
        ]);
    }
}
