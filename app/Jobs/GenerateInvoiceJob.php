<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Invoices\GenerateInvoice;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Renders the invoice for a paid order (M2.8).
 *
 * Queued for the same reason provisioning is: PDF rendering is slow and the
 * webhook has to answer the gateway in seconds. The action is idempotent on the
 * order, so a redelivered settlement or a retry produces one invoice.
 */
class GenerateInvoiceJob implements ShouldQueue
{
    use Queueable;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 60, 300];

    public int $tries = 4;

    public function __construct(public readonly int $orderId) {}

    public function handle(GenerateInvoice $generate): void
    {
        $order = Order::query()->find($this->orderId);

        if ($order === null) {
            Log::warning('Invoice dilewati: pesanan tidak ditemukan.', ['order_id' => $this->orderId]);

            return;
        }

        // Only a sale that happened gets a document.
        if (! $order->status->isSettled()) {
            Log::warning('Invoice dilewati: pesanan belum dibayar.', [
                'order' => $order->order_number,
                'status' => $order->status->value,
            ]);

            return;
        }

        $generate($order);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Pembuatan invoice gagal.', [
            'order_id' => $this->orderId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
