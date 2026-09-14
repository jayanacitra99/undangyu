<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Package;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Turns a package (and optionally a template) into a pending order (M2.4).
 *
 * Everything money-related is snapshotted into `order_items` at this moment.
 * A later price change on the package or the template must not move what this
 * order says it cost — see the warning in docs/03 § 3.2.
 */
final class CreateOrder
{
    /**
     * How long a client has to pay before the hourly sweep expires the order.
     */
    public const PAYMENT_WINDOW_HOURS = 24;

    /**
     * How many times to retry when two checkouts mint the same order number.
     */
    private const MAX_ATTEMPTS = 5;

    public function __construct(private readonly GenerateOrderNumber $generateOrderNumber) {}

    public function __invoke(User $user, Package $package, ?Template $template = null): Order
    {
        // The unique index on order_number is what actually guarantees
        // uniqueness; a collision means another checkout committed first, so
        // read the sequence again rather than failing the customer's purchase.
        for ($attempt = 1; ; $attempt++) {
            try {
                return $this->create($user, $package, $template);
            } catch (QueryException $exception) {
                if ($attempt >= self::MAX_ATTEMPTS || ! $this->isDuplicateOrderNumber($exception)) {
                    throw $exception;
                }
            }
        }
    }

    private function create(User $user, Package $package, ?Template $template): Order
    {
        return DB::transaction(function () use ($user, $package, $template): Order {
            $packagePrice = $package->effectivePrice();
            $templatePrice = $template !== null && $template->is_premium
                ? (float) $template->extra_price
                : 0.0;

            $subtotal = $packagePrice + $templatePrice;

            // No coupon engine (M2.12) and no tax rate is set for the product
            // yet. Both columns stay in the arithmetic so adding either later
            // is a change here and nowhere else.
            $discount = 0.0;
            $tax = 0.0;

            $order = Order::query()->create([
                'order_number' => ($this->generateOrderNumber)(),
                'user_id' => $user->getKey(),
                'package_id' => $package->getKey(),
                'template_id' => $template?->getKey(),
                'coupon_id' => null,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'total' => $subtotal - $discount + $tax,
                'status' => OrderStatus::Pending,
                'payment_deadline' => now()->addHours(self::PAYMENT_WINDOW_HOURS),
            ]);

            $order->items()->create([
                'itemable_type' => $package->getMorphClass(),
                'itemable_id' => $package->getKey(),
                'name' => $package->name,
                'unit_price' => $packagePrice,
                'quantity' => 1,
                'total' => $packagePrice,
                'meta' => [
                    'slug' => $package->slug,
                    'active_days' => $package->active_days,
                ],
            ]);

            // A template only becomes a line of its own when it costs extra;
            // a template included in the tier is recorded on the order itself.
            if ($template !== null && $templatePrice > 0) {
                $order->items()->create([
                    'itemable_type' => $template->getMorphClass(),
                    'itemable_id' => $template->getKey(),
                    'name' => $template->name,
                    'unit_price' => $templatePrice,
                    'quantity' => 1,
                    'total' => $templatePrice,
                    'meta' => [
                        'slug' => $template->slug,
                        'version' => $template->version,
                    ],
                ]);
            }

            return $order;
        });
    }

    private function isDuplicateOrderNumber(QueryException $exception): bool
    {
        // 23000 covers MySQL's integrity-constraint violations, of which the
        // unique index on order_number is the only one this insert can hit.
        return $exception->getCode() === '23000'
            && str_contains($exception->getMessage(), 'order_number');
    }
}
