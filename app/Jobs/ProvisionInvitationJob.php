<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Turns a paid order into an invitation (M2.9).
 *
 * A stub until Session 11: the webhook's contract is that provisioning is
 * dispatched and never runs inline — gateways time out at 5–10s — so the job
 * exists now and gains its body next session.
 */
class ProvisionInvitationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $orderId) {}

    /**
     * The gateway can deliver the same settlement more than once, and a lost
     * queue worker retries too, so Session 11's body has to be idempotent on
     * the order — one invitation per order, however often this runs.
     */
    public function handle(): void
    {
        Log::info('Provisioning ditunda sampai Sesi 11.', ['order_id' => $this->orderId]);
    }
}
