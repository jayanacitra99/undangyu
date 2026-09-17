<?php

declare(strict_types=1);

namespace App\Services\Payment\Drivers;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\Data\PaymentSession;
use App\Services\Payment\Data\PaymentUpdate;
use App\Services\Payment\Data\RefundResult;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * A gateway that settles instantly and touches no network (playbook 9.6).
 *
 * Feature tests run on this, and so does a local box with no sandbox
 * credentials. It is registered in the driver map like any other gateway, so
 * choosing it is a config change rather than a special case in the code.
 */
final class FakeGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'fake';
    }

    public function createTransaction(Payment $payment): PaymentSession
    {
        $reference = 'FAKE-'.$payment->order->order_number.'-'.Str::upper(Str::random(6));

        return new PaymentSession(
            reference: $reference,
            // Lands on the order the client is already looking at, so a test
            // can follow the redirect without a route that only exists here.
            redirectUrl: route('client.orders.show', $payment->order),
            token: Str::random(32),
            raw: ['driver' => 'fake', 'reference' => $reference],
        );
    }

    /**
     * Every request is authentic: there is no secret to sign with, and a test
     * asserting signature rejection should use the real driver.
     */
    public function verifyWebhook(Request $request): bool
    {
        return true;
    }

    public function parseWebhook(Request $request): PaymentUpdate
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->all();

        $status = PaymentStatus::tryFrom((string) ($payload['status'] ?? ''))
            ?? PaymentStatus::Settled;

        return new PaymentUpdate(
            reference: (string) ($payload['reference'] ?? $payload['order_id'] ?? ''),
            status: $status,
            method: (string) ($payload['method'] ?? 'fake_transfer'),
            amount: isset($payload['amount']) ? (string) $payload['amount'] : null,
            paidAt: now()->toIso8601String(),
            raw: $payload,
        );
    }

    /**
     * The fake gateway agrees with whatever the local record says, so
     * reconciliation reports no discrepancies against it.
     */
    public function fetchStatus(Payment $payment): ?PaymentUpdate
    {
        if ($payment->gateway_ref === null) {
            return null;
        }

        return new PaymentUpdate(
            reference: $payment->gateway_ref,
            status: $payment->status,
            method: $payment->method,
            amount: (string) $payment->amount,
            paidAt: $payment->paid_at?->toIso8601String(),
            raw: ['driver' => 'fake'],
        );
    }

    public function refund(Payment $payment, ?string $amount = null): RefundResult
    {
        return new RefundResult(
            successful: true,
            reference: 'FAKE-REFUND-'.Str::upper(Str::random(6)),
            amount: $amount ?? (string) $payment->amount,
            message: 'Refund berhasil (driver fake).',
            raw: ['driver' => 'fake'],
        );
    }
}
