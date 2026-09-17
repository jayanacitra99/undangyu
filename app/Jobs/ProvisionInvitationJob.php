<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Invitations\ProvisionInvitation;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Notifications\InvitationProvisioned;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Turns a paid order into an invitation (M2.9).
 *
 * Never runs inline: gateways time out at 5–10 seconds and the webhook has to
 * answer long before this finishes. The action it calls is idempotent on the
 * order, so a redelivered settlement or a retried job produces one invitation.
 */
class ProvisionInvitationJob implements ShouldQueue
{
    use Queueable;

    /**
     * Provisioning touches several tables; a transient failure deserves
     * another go, spaced out rather than hammering.
     *
     * @var list<int>
     */
    public array $backoff = [10, 60, 300];

    public int $tries = 4;

    public function __construct(public readonly int $orderId) {}

    public function handle(ProvisionInvitation $provision): void
    {
        $order = Order::query()->with(['user', 'package'])->find($this->orderId);

        if ($order === null) {
            Log::warning('Provisioning dilewati: pesanan tidak ditemukan.', ['order_id' => $this->orderId]);

            return;
        }

        // Only money that actually arrived provisions anything. `provisioned`
        // is included so a retry after a partial failure can finish the work.
        if (! in_array($order->status, [OrderStatus::Paid, OrderStatus::Provisioned], true)) {
            Log::warning('Provisioning dilewati: pesanan belum dibayar.', [
                'order' => $order->order_number,
                'status' => $order->status->value,
            ]);

            return;
        }

        $alreadyProvisioned = $order->invitation()->exists();

        $invitation = $provision($order);

        if ($alreadyProvisioned) {
            // A redelivered webhook or a retried job. The invitation stands;
            // the client does not get told about it twice.
            return;
        }

        $order->user->notify(new InvitationProvisioned($invitation));
    }

    /**
     * The order stays `paid` when this gives up, so `payments:reconcile` and
     * the admin order screen both still show it as needing attention.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Provisioning gagal.', [
            'order_id' => $this->orderId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
