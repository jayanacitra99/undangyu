<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RsvpAttendance;
use App\Models\Invitation;
use App\Models\Rsvp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rsvp>
 */
class RsvpFactory extends Factory
{
    protected $model = Rsvp::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invitation_id' => Invitation::factory(),
            'guest_id' => null,
            'invitation_event_id' => null,
            'name' => fake()->randomElement(['Budi Santoso', 'Siti Rahayu', 'Agus Setiawan', 'Dewi Lestari']),
            'phone' => '+628'.fake()->numerify('##########'),
            'attendance' => RsvpAttendance::Yes->value,
            'pax' => fake()->numberBetween(1, 3),
            'meal_preference' => null,
            'notes' => null,
            'ip_hash' => hash('sha256', fake()->ipv4()),
            'user_agent' => 'Mozilla/5.0 (Linux; Android 13)',
            'responded_at' => now(),
        ];
    }

    public function declined(): self
    {
        return $this->state(fn (): array => [
            'attendance' => RsvpAttendance::No->value,
            'pax' => 0,
            'notes' => 'Mohon maaf, berhalangan hadir.',
        ]);
    }

    public function maybe(): self
    {
        return $this->state(fn (): array => [
            'attendance' => RsvpAttendance::Maybe->value,
            'pax' => 1,
        ]);
    }
}
