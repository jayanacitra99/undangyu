<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Package;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomElement([99000, 199000, 249000, 599000]);

        return [
            'order_number' => 'UDY-'.now()->format('Ymd').'-'.str_pad((string) fake()->unique()->numberBetween(1, 99999), 4, '0', STR_PAD_LEFT),
            'user_id' => User::factory(),
            'package_id' => Package::factory(),
            'template_id' => null,
            'coupon_id' => null,
            'subtotal' => $subtotal,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => $subtotal,
            'status' => OrderStatus::Pending,
            'payment_deadline' => now()->addDay(),
            'paid_at' => null,
            'notes' => null,
        ];
    }

    public function paid(): self
    {
        return $this->state(fn (): array => [
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    public function expired(): self
    {
        return $this->state(fn (): array => [
            'status' => OrderStatus::Expired,
            'payment_deadline' => now()->subDay(),
        ]);
    }

    /**
     * Pending, but past its deadline — what the hourly sweep looks for.
     */
    public function overdue(): self
    {
        return $this->state(fn (): array => [
            'status' => OrderStatus::Pending,
            'payment_deadline' => now()->subHour(),
        ]);
    }
}
