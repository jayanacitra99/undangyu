<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Console\Command;

/**
 * Expires pending orders whose payment deadline has passed (M2.10).
 *
 * Runs hourly. The update is a single statement over the
 * (status, payment_deadline) index rather than a model loop, so a backlog of
 * thousands costs one query — no model events fire, which is correct here:
 * nothing should be notified that an unpaid order lapsed.
 */
class ExpireOrders extends Command
{
    protected $signature = 'orders:expire';

    protected $description = 'Mark pending orders past their payment deadline as expired';

    public function handle(): int
    {
        $expired = Order::query()->overdue()->update([
            'status' => OrderStatus::Expired,
            'updated_at' => now(),
        ]);

        $this->info($expired === 0
            ? 'No orders to expire.'
            : "Expired {$expired} order(s).");

        return self::SUCCESS;
    }
}
