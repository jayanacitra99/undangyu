<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $package = Package::factory();
        $price = fake()->randomElement([99000, 199000, 249000]);

        return [
            'order_id' => Order::factory(),
            'itemable_type' => (new Package)->getMorphClass(),
            'itemable_id' => $package,
            'name' => fake()->randomElement(['Free', 'Basic', 'Premium', 'Exclusive']),
            'unit_price' => $price,
            'quantity' => 1,
            'total' => $price,
            'meta' => null,
        ];
    }
}
