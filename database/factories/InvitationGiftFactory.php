<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GiftType;
use App\Models\Invitation;
use App\Models\InvitationGift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvitationGift>
 */
class InvitationGiftFactory extends Factory
{
    protected $model = InvitationGift::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invitation_id' => Invitation::factory(),
            'type' => GiftType::Bank->value,
            'provider_name' => fake()->randomElement(['BCA', 'Mandiri', 'BNI', 'BRI']),
            'account_name' => fake()->randomElement(['Ayu Lestari', 'Bagus Prakoso']),
            'account_number' => (string) fake()->numerify('##########'),
            'qris_image' => null,
            'recipient_name' => null,
            'address' => null,
            'notes' => null,
            'sort_order' => fake()->numberBetween(0, 3),
        ];
    }

    public function ewallet(): self
    {
        return $this->state(fn (): array => [
            'type' => GiftType::Ewallet->value,
            'provider_name' => fake()->randomElement(['GoPay', 'OVO', 'DANA', 'ShopeePay']),
            'account_number' => '+628'.fake()->numerify('##########'),
        ]);
    }

    public function qris(): self
    {
        return $this->state(fn (): array => [
            'type' => GiftType::Qris->value,
            'provider_name' => 'QRIS',
            'account_name' => null,
            'account_number' => null,
            'qris_image' => 'invitations/gifts/qris.png',
        ]);
    }

    /**
     * A postal address, for guests who would rather send a parcel.
     */
    public function address(): self
    {
        return $this->state(fn (): array => [
            'type' => GiftType::Address->value,
            'provider_name' => null,
            'account_name' => null,
            'account_number' => null,
            'recipient_name' => 'Ayu Lestari',
            'address' => 'Perumahan Griya Asri Blok C2 No. 14, Sidoarjo, Jawa Timur 61256',
            'notes' => 'Mohon konfirmasi sebelum mengirim.',
        ]);
    }
}
