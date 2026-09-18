<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GuestGroup;
use App\Models\Invitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuestGroup>
 */
class GuestGroupFactory extends Factory
{
    protected $model = GuestGroup::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invitation_id' => Invitation::factory(),
            'name' => fake()->randomElement(['Keluarga', 'Teman Kuliah', 'Rekan Kantor', 'Tetangga', 'Teman SMA']),
            'color' => fake()->randomElement(['#0d6efd', '#198754', '#dc3545', '#fd7e14', '#6f42c1']),
            'sort_order' => fake()->numberBetween(0, 5),
        ];
    }
}
