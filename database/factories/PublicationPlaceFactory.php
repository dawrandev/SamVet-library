<?php

namespace Database\Factories;

use App\Models\PublicationPlace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PublicationPlace>
 */
class PublicationPlaceFactory extends Factory
{
    protected $model = PublicationPlace::class;

    public function definition(): array
    {
        $city = $this->faker->unique()->city();

        return [
            'name' => ['uz' => $city, 'ru' => $city, 'kk' => $city],
        ];
    }
}
