<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Invitation;
use App\Models\InvitationStory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvitationStory>
 */
class InvitationStoryFactory extends Factory
{
    protected $model = InvitationStory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $moment = fake()->randomElement([
            ['Pertama Bertemu', 'Dipertemukan di acara kampus, lalu ngobrol sampai kantin tutup.'],
            ['Mulai Dekat', 'Dari teman belajar jadi teman cerita setiap hari.'],
            ['Jadian', 'Sepakat menjalani semuanya bersama, di sebuah sore di Malioboro.'],
            ['Lamaran', 'Keluarga bertemu, doa dipanjatkan, tanggal ditentukan.'],
        ]);

        return [
            'invitation_id' => Invitation::factory(),
            'date' => fake()->dateTimeBetween('-6 years', '-2 months'),
            'title' => $moment[0],
            'description' => $moment[1],
            'image' => null,
            'sort_order' => fake()->numberBetween(0, 3),
        ];
    }
}
