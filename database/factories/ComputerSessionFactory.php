<?php

namespace Database\Factories;

use App\Models\Computer;
use App\Models\ComputerSession;
use App\Models\Reader;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComputerSession>
 */
class ComputerSessionFactory extends Factory
{
    protected $model = ComputerSession::class;

    public function definition(): array
    {
        $issuedAt = now();

        return [
            'reader_id' => Reader::factory(),
            'computer_id' => Computer::factory(),
            'issued_at' => $issuedAt,
            'duration_minutes' => 60,
            'expires_at' => $issuedAt->clone()->addMinutes(60),
            'location' => 'reading_hall',
            'purpose' => null,
            'note' => null,
        ];
    }

    /** Time ran out a while ago, nobody finished it. */
    public function expired(): static
    {
        return $this->state(fn () => [
            'issued_at' => now()->subHours(2),
            'expires_at' => now()->subHour(),
        ]);
    }

    /** Already finished ("Tugatish" clicked). */
    public function finished(): static
    {
        return $this->state(fn () => [
            'issued_at' => now()->subHour(),
            'expires_at' => now()->subMinutes(30),
            'returned_at' => now()->subMinutes(20),
        ]);
    }
}
