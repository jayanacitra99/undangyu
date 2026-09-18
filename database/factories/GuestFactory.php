<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Guest;
use App\Models\Invitation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Guest>
 */
class GuestFactory extends Factory
{
    protected $model = Guest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Budi Santoso', 'Siti Rahayu', 'Agus Setiawan', 'Dewi Lestari', 'Rizki Ramadhan',
            'Ayu Puspita', 'Bambang Wijaya', 'Nur Aisyah', 'Eko Prasetyo', 'Rina Marlina',
        ]).' '.fake()->numerify('##');

        return [
            'invitation_id' => Invitation::factory(),
            'guest_group_id' => null,
            'title' => fake()->randomElement(Guest::TITLES),
            'name' => $name,
            'phone' => '+628'.fake()->numerify('##########'),
            'email' => fake()->optional()->safeEmail(),
            'address' => fake()->optional()->address(),
            // The factory does not go through GenerateGuestToken: a factory
            // that needs the container to make one row makes seeding a
            // thousand of them slower for no extra realism.
            'token' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'max_pax' => fake()->numberBetween(1, 4),
            'is_vip' => fake()->boolean(10),
            'table_number' => null,
            'notes' => null,
            'sent_at' => null,
            'opened_at' => null,
            'open_count' => 0,
        ];
    }

    public function vip(): self
    {
        return $this->state(fn (): array => [
            'is_vip' => true,
            'table_number' => 'VIP-'.fake()->numberBetween(1, 5),
        ]);
    }

    /**
     * Sent and opened — what a client looks at when chasing non-openers.
     */
    public function opened(): self
    {
        return $this->state(fn (): array => [
            'sent_at' => now()->subDays(3),
            'opened_at' => now()->subDays(2),
            'open_count' => fake()->numberBetween(1, 9),
        ]);
    }
}
