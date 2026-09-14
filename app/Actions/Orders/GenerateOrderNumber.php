<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Models\Order;
use Illuminate\Support\Carbon;

/**
 * The next `UDY-{Ymd}-{0001}` for a given day (playbook 8.3).
 *
 * The sequence is per day and derived from the highest number already issued
 * that day. Two checkouts racing can read the same highest value, so this is
 * only half the guarantee: the unique index on `orders.order_number` is the
 * other half, and CreateOrder retries when it fires.
 */
final class GenerateOrderNumber
{
    public const PREFIX = 'UDY';

    public const PAD = 4;

    public function __invoke(?Carbon $date = null): string
    {
        $date ??= now();
        $prefix = self::PREFIX.'-'.$date->format('Ymd').'-';

        $highest = Order::query()
            ->where('order_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->max('order_number');

        $sequence = $highest === null
            ? 0
            : (int) substr((string) $highest, strlen($prefix));

        return $prefix.str_pad((string) ($sequence + 1), self::PAD, '0', STR_PAD_LEFT);
    }
}
