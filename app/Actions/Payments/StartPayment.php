<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\Data\PaymentSession;
use Illuminate\Support\Facades\DB;

/**
 * Opens a gateway transaction for an order and records the attempt (M2.5).
 *
 * The local row is written first, without a reference, so the gateway call has
 * a payment id to key its own order id on. If the gateway then refuses, the
 * row is rolled back rather than left behind as a payment that never existed.
 *
 * A pending attempt is reused rather than duplicated: a client who returns to
 * an unfinished payment should land back on the same Snap page, not mint a
 * second reference against one order.
 */
final class StartPayment
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    /**
     * @return array{payment: Payment, session: PaymentSession}
     */
    public function __invoke(Order $order): array
    {
        $existing = $order->payments()
            ->where('gateway', $this->gateway->name())
            ->pending()
            ->whereNotNull('gateway_ref')
            ->latest('id')
            ->first();

        if ($existing !== null && isset($existing->raw_payload['redirect_url'])) {
            return [
                'payment' => $existing,
                'session' => new PaymentSession(
                    reference: (string) $existing->gateway_ref,
                    redirectUrl: (string) $existing->raw_payload['redirect_url'],
                    token: $existing->raw_payload['token'] ?? null,
                    raw: $existing->raw_payload,
                ),
            ];
        }

        $order->loadMissing(['items', 'user']);

        return DB::transaction(function () use ($order): array {
            $payment = $order->payments()->create([
                'gateway' => $this->gateway->name(),
                'amount' => $order->total,
                'status' => PaymentStatus::Pending,
            ]);

            // A gateway refusal throws, which rolls the transaction back: no
            // payment row survives for a transaction that was never opened.
            $session = $this->gateway->createTransaction($payment);

            $payment->update([
                'gateway_ref' => $session->reference,
                'raw_payload' => [
                    'redirect_url' => $session->redirectUrl,
                    'token' => $session->token,
                    'response' => $session->raw,
                ],
            ]);

            return ['payment' => $payment->refresh(), 'session' => $session];
        });
    }
}
