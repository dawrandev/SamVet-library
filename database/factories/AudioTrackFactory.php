<?php

namespace Database\Factories;

use App\Models\Audiobook;
use App\Models\AudioTrack;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AudioTrack>
 */
class AudioTrackFactory extends Factory
{
    protected $model = AudioTrack::class;

    public function definition(): array
    {
        return [
            'audiobook_id' => Audiobook::factory(),
            'title' => $this->faker->numberBetween(1, 20).'-qism',
            'audio_file' => 'audiobooks/audio/'.$this->faker->uuid().'.mp3',
            'sort_order' => 0,
        ];
    }
}
