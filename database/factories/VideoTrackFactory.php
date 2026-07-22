<?php

namespace Database\Factories;

use App\Models\Video;
use App\Models\VideoTrack;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VideoTrack>
 */
class VideoTrackFactory extends Factory
{
    protected $model = VideoTrack::class;

    public function definition(): array
    {
        return [
            'video_id' => Video::factory(),
            'title' => $this->faker->numberBetween(1, 20).'-qism',
            'video_file' => 'videos/video/'.$this->faker->uuid().'.mp4',
            'sort_order' => 0,
        ];
    }
}
