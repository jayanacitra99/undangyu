<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Enums\OrderStatus;
use App\Jobs\GenerateInvoiceJob;
use App\Jobs\ProvisionInvitationJob;
use App\Models\Payment;
use App\Services\Payment\Data\PaymentUpdate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Applies one gateway notification to the local records (M2.6).
 *
 * The idempotency lives here rather than in the controller: the payment row is
 * taken with `lockForUpdate()` inside a transaction, and a payment that is
 * already settled is a no-op. Five identical deliveries therefore pay one order
 * and dispatch one provisioning job.
 *
 * Provisioning is dispatched after the transaction commits, so a worker can
 * never pick the job up before the row it needs is visible.
 */
final class ApplyPaymentUpdate
{
    /**
     * @return bool false when no local payment carries this reference
     */
    public function __invoke(PaymentUpdate $update, string $gateway): bool
    {
        if ($update->reference === '') {
            return false;
        }

        $provisionOrderId = null;

        $found = DB::transaction(function () use ($update, $gateway, &$provisionOrderId): bool {
            $payment = Payment::query()
                ->where('gateway_ref', $update->reference)
                ->lockForUpdate()
                ->first();

            if ($payment === null) {
                return false;
            }

            // The raw payload is written even for a status we already hold, so
            // the last thing the gateway said is always on the record.
            $payment->forceFill([
                'raw_payload' => $update->raw,
                'method' => $update->method ?? $payment->method,
            ]);

            if ($payment->status->isSettled()) {
                // Already paid. Record what arrived and stop — no second job,
                // no moved paid_at.
                $payment->save();

                return true;
            }

            $payment->status = $update->status;

            if ($update->status->isSettled()) {
                $payment->paid_at = $this->paidAt($update);
            }

            $payment->save();

            if (! $update->status->isSettled()) {
                return true;
            }

            $order = $payment->order()->lockForUpdate()->first();

            if ($order === null) {
                return true;
            }

            if ($order->status === OrderStatus::Expired) {
                // The deadline passed before the money arrived. Reviving the
                // order is the decision on record; it must never be quiet.
                Log::warning('Pembayaran diterima untuk pesanan yang sudah kedaluwarsa.', [
                    'order' => $order->order_number,
                    'gateway' => $gateway,
                    'reference' => $update->reference,
                ]);
            }

            if ($order->status->isSettled()) {
                // Another payment already paid this order — the money is real,
                // but the invitation exists, so nothing more is dispatched.
                return true;
            }

            $order->update([
                'status' => OrderStatus::Paid,
                'paid_at' => $payment->paid_at,
            ]);

            $provisionOrderId = $order->getKey();

            return true;
        });

        if ($provisionOrderId !== null) {
            Bus::dispatch(new ProvisionInvitationJob($provisionOrderId));
            // The document for the sale, rendered off the queue for the same
            // reason: the gateway is waiting on this response.
            Bus::dispatch(new GenerateInvoiceJob($provisionOrderId));
        }

        return $found;
    }

    private function paidAt(PaymentUpdate $update): Carbon
    {
        if ($update->paidAt === null) {
            return now();
        }

        // Gateways send their own local time; an unparseable one is not worth
        // failing a settlement over.
        try {
            return Carbon::parse($update->paidAt);
        } catch (\Throwable) {
            return now();
        }
    }
}
