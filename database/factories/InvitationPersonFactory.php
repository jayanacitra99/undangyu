<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PersonRole;
use App\Models\Invitation;
use App\Models\InvitationPerson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvitationPerson>
 */
class InvitationPersonFactory extends Factory
{
    protected $model = InvitationPerson::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Ayu Lestari', 'Bagus Prakoso', 'Sekar Wulandari', 'Raka Adhitama',
            'Dinda Pratiwi', 'Yoga Mahendra', 'Ratna Kusuma', 'Dimas Saputra',
        ]);

        return [
            'invitation_id' => Invitation::factory(),
            'role' => PersonRole::Bride->value,
            'full_name' => $name.', S.Kom.',
            'nickname' => explode(' ', $name)[0],
            'photo' => null,
            'bio' => null,
            'parent_father' => 'Bapak '.fake()->randomElement(['Suryanto', 'Hartono', 'Wijaya', 'Nugroho']),
            'parent_mother' => 'Ibu '.fake()->randomElement(['Sri Rahayu', 'Endang Susanti', 'Tuti Herawati']),
            'child_order' => fake()->randomElement([
                'Putri pertama dari dua bersaudara',
                'Putra kedua dari tiga bersaudara',
                'Putri bungsu dari empat bersaudara',
            ]),
            'instagram' => '@'.fake()->userName(),
            'sort_order' => 0,
        ];
    }

    public function role(PersonRole $role, int $sortOrder = 0): self
    {
        return $this->state(fn (): array => [
            'role' => $role->value,
            'sort_order' => $sortOrder,
        ]);
    }

    public function bride(): self
    {
        return $this->role(PersonRole::Bride, 0)->state(fn (): array => [
            'full_name' => 'Ayu Lestari, S.Kom.',
            'nickname' => 'Ayu',
        ]);
    }

    public function groom(): self
    {
        return $this->role(PersonRole::Groom, 1)->state(fn (): array => [
            'full_name' => 'Bagus Prakoso, S.T.',
            'nickname' => 'Bagus',
        ]);
    }
}
