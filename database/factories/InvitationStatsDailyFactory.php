<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Invitation;
use App\Models\InvitationStatsDaily;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvitationStatsDaily>
 */
class InvitationStatsDailyFactory extends Factory
{
    protected $model = InvitationStatsDaily::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $views = fake()->numberBetween(10, 400);

        return [
            'invitation_id' => Invitation::factory(),
            'date' => now()->toDateString(),
            'views' => $views,
            'unique_visitors' => (int) round($views * 0.7),
            'rsvp_yes' => fake()->numberBetween(0, 20),
            'rsvp_no' => fake()->numberBetween(0, 5),
            'rsvp_maybe' => fake()->numberBetween(0, 5),
            'total_pax' => fake()->numberBetween(0, 40),
            'wishes_count' => fake()->numberBetween(0, 15),
            'guest_opens' => fake()->numberBetween(0, 50),
        ];
    }
}
