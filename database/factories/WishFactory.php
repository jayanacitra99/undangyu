<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WishStatus;
use App\Models\Invitation;
use App\Models\Wish;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wish>
 */
class WishFactory extends Factory
{
    protected $model = Wish::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invitation_id' => Invitation::factory(),
            'guest_id' => null,
            'name' => fake()->randomElement(['Budi Santoso', 'Siti Rahayu', 'Agus Setiawan', 'Dewi Lestari']),
            'message' => fake()->randomElement([
                'Selamat menempuh hidup baru, semoga samawa!',
                'Barakallahu lakuma wa baraka alaikuma.',
                'Turut berbahagia, semoga langgeng sampai kakek nenek.',
            ]),
            'status' => WishStatus::Approved->value,
            'is_pinned' => false,
            'ip_hash' => hash('sha256', fake()->ipv4()),
        ];
    }

    public function pending(): self
    {
        return $this->state(fn (): array => ['status' => WishStatus::Pending->value]);
    }

    public function rejected(): self
    {
        return $this->state(fn (): array => ['status' => WishStatus::Rejected->value]);
    }

    public function pinned(): self
    {
        return $this->state(fn (): array => ['is_pinned' => true]);
    }
}
