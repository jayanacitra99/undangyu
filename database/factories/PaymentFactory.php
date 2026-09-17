<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'gateway' => 'midtrans',
            'gateway_ref' => 'UDY-'.now()->format('Ymd').'-'.fake()->unique()->numberBetween(1000, 9999),
            'method' => fake()->randomElement(['bca_va', 'qris', 'gopay', 'bank_transfer']),
            'amount' => fake()->randomElement([99000, 199000, 249000, 599000]),
            'status' => PaymentStatus::Pending,
            'proof_path' => null,
            'verified_by' => null,
            'paid_at' => null,
            'raw_payload' => null,
        ];
    }

    public function settled(): self
    {
        return $this->state(fn (): array => [
            'status' => PaymentStatus::Settled,
            'paid_at' => now(),
        ]);
    }

    public function failed(): self
    {
        return $this->state(fn (): array => ['status' => PaymentStatus::Failed]);
    }

    public function expired(): self
    {
        return $this->state(fn (): array => ['status' => PaymentStatus::Expired]);
    }

    /**
     * A manual bank transfer awaiting admin verification (M2.7).
     */
    public function manual(): self
    {
        return $this->state(fn (): array => [
            'gateway' => 'manual',
            'gateway_ref' => null,
            'method' => 'bank_transfer',
            'proof_path' => 'payments/proofs/'.Str::random(12).'.jpg',
        ]);
    }
}
