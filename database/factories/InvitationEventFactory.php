<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Invitation;
use App\Models\InvitationEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<InvitationEvent>
 */
class InvitationEventFactory extends Factory
{
    protected $model = InvitationEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $venue = fake()->randomElement([
            ['Gedung Serbaguna Puri Agung', 'Jl. Raya Kuta No. 88, Denpasar, Bali'],
            ['Hotel Majapahit', 'Jl. Tunjungan No. 65, Surabaya, Jawa Timur'],
            ['Balai Kartini', 'Jl. Gatot Subroto Kav. 37, Jakarta Selatan'],
            ['Pendopo Kabupaten Sleman', 'Jl. Magelang KM 10, Sleman, Yogyakarta'],
        ]);

        // Stored UTC. 11:00 UTC is 18:00 WIB, a plausible reception hour.
        $start = Carbon::parse('next saturday')->setTime(11, 0)->addWeeks(fake()->numberBetween(2, 20));

        return [
            'invitation_id' => Invitation::factory(),
            'name' => 'Resepsi',
            'description' => null,
            'start_at' => $start,
            'end_at' => $start->copy()->addHours(4),
            'is_all_day' => false,
            'venue_name' => $venue[0],
            'address' => $venue[1],
            'maps_url' => 'https://maps.app.goo.gl/'.fake()->lexify('??????????'),
            'latitude' => fake()->latitude(-8.8, -6.1),
            'longitude' => fake()->longitude(106.7, 115.3),
            'dress_code' => fake()->randomElement(['Batik', 'Formal', 'Earth tone', 'Putih & emas']),
            'live_stream_url' => null,
            'notes' => null,
            'sort_order' => 1,
        ];
    }

    /**
     * The ceremony, which in Indonesia usually runs the same morning.
     */
    public function akad(): self
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Akad Nikah',
            'start_at' => Carbon::parse($attributes['start_at'])->copy()->setTime(2, 0),
            'end_at' => Carbon::parse($attributes['start_at'])->copy()->setTime(4, 0),
            'dress_code' => 'Busana muslim',
            'sort_order' => 0,
        ]);
    }

    public function resepsi(): self
    {
        return $this->state(fn (): array => ['name' => 'Resepsi', 'sort_order' => 1]);
    }
}
