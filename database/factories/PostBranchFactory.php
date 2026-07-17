<?php

namespace Database\Factories;

use App\Models\PostBranch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostBranch>
 */
class PostBranchFactory extends Factory
{
    protected $model = PostBranch::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->city().' pochtasi',
        ];
    }
}
