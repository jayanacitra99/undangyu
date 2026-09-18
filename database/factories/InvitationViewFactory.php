<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Invitation;
use App\Models\InvitationView;
use App\Support\DeviceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvitationView>
 */
class InvitationViewFactory extends Factory
{
    protected $model = InvitationView::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invitation_id' => Invitation::factory(),
            'guest_id' => null,
            'ip_hash' => hash('sha256', fake()->ipv4()),
            'user_agent' => 'Mozilla/5.0 (Linux; Android 13; SM-A536E)',
            'referrer' => null,
            // Overwhelmingly a phone, which is what the real data looks like.
            'device_type' => fake()->randomElement([
                DeviceType::MOBILE,
                DeviceType::MOBILE,
                DeviceType::MOBILE,
                DeviceType::DESKTOP,
            ]),
            'country' => 'ID',
            'viewed_at' => now(),
        ];
    }
}
