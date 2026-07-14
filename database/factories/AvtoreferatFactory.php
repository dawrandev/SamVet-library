<?php

namespace Database\Factories;

use App\Enums\CopyCondition;
use App\Enums\DissertationDegree;
use App\Models\Avtoreferat;
use App\Models\PublicationPlace;
use App\Models\ResourceField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Avtoreferat>
 */
class AvtoreferatFactory extends Factory
{
    protected $model = Avtoreferat::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->unique()->sentence(5),
            'author' => $this->faker->name(),
            'specialty' => $this->faker->numerify('##.##.##') . ' – ' . $this->faker->words(3, true),
            'degree' => $this->faker->randomElement(DissertationDegree::cases()),
            'council_number' => $this->faker->numerify('DSc.##/##.##.####.B.##.##'),
            'defense_institution' => $this->faker->company() . ' universiteti',
            'performed_institution' => $this->faker->company() . ' universiteti',
            'advisor' => $this->faker->name(),
            'udc' => $this->faker->numerify('###.#'),
            'registration_number' => $this->faker->numerify('B##.##'),
            'condition' => $this->faker->randomElement(CopyCondition::cases()),
            'publication_place_id' => PublicationPlace::factory(),
            'publication_year' => $this->faker->numberBetween(2000, (int) date('Y')),
            'inventory_number' => $this->faker->numerify('INV-#####'),
            'resource_field_id' => ResourceField::factory(),
            'annotation' => $this->faker->paragraph(),
            // slug is set by the observer
        ];
    }
}
