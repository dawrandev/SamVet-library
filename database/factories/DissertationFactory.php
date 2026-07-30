<?php

namespace Database\Factories;

use App\Models\Dissertation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dissertation>
 */
class DissertationFactory extends Factory
{
    protected $model = Dissertation::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->unique()->sentence(5),
            'author' => $this->faker->name(),
            'acquisition_act_number' => $this->faker->optional()->numerify('KA-####'),
            'acquisition_act_at' => $this->faker->optional()->date(),
            'annotation' => $this->faker->paragraph(),
            // slug is set by the observer
        ];
    }

    public function withPdf(string $path = 'dissertations/electronic/test.pdf'): static
    {
        return $this->state(fn () => ['electronic_file' => $path]);
    }
}
