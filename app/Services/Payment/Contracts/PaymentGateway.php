<?php

declare(strict_types=1);

namespace App\Services\Payment\Contracts;

use App\Models\Payment;
use App\Services\Payment\Data\PaymentSession;
use App\Services\Payment\Data\PaymentUpdate;
use App\Services\Payment\Data\RefundResult;
use Illuminate\Http\Request;

/**
 * Everything the application is allowed to know about a payment gateway
 * (docs/05 § 6).
 *
 * Swapping Midtrans for Xendit must cost one class implementing this, plus a
 * line in config/payment.php — nothing outside app/Services/Payment may name a
 * gateway or touch its payload shape.
 */
interface PaymentGateway
{
    /**
     * The short name this driver writes into `payments.gateway`.
     */
    public function name(): string;

    /**
     * Open a transaction for an order and hand back where to send the client.
     */
    public function createTransaction(Payment $payment): PaymentSession;

    /**
     * Is this request really from the gateway? Called before anything else
     * touches the payload (hard rule 3).
     */
    public function verifyWebhook(Request $request): bool;

    /**
     * The gateway's payload as something the application understands. Only ever
     * called after verifyWebhook() has returned true.
     */
    public function parseWebhook(Request $request): PaymentUpdate;

    /**
     * What the gateway itself believes about a payment.
     *
     * A fifth method beyond the four in docs/05 § 6, added for M2.6's daily
     * reconciliation: webhooks do get lost, and the only way to notice is to
     * ask. Returns null when the gateway has no record of the reference.
     */
    public function fetchStatus(Payment $payment): ?PaymentUpdate;

    /**
     * Refund all of a payment, or part of it. The amount is a decimal string,
     * like every money value in the system — a partial refund is the one place
     * fractional money is plausible, and a float would round it away.
     */
    public function refund(Payment $payment, ?string $amount = null): RefundResult;
}
