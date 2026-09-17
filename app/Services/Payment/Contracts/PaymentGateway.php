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
     * Refund all of a payment, or part of it.
     */
    public function refund(Payment $payment, ?float $amount = null): RefundResult;
}
