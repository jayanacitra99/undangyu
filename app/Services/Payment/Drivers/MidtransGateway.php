<?php

declare(strict_types=1);

namespace App\Services\Payment\Drivers;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\Data\PaymentSession;
use App\Services\Payment\Data\PaymentUpdate;
use App\Services\Payment\Data\RefundResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Midtrans Snap (M2.5), talked to over HTTP rather than through the SDK.
 *
 * Snap is three calls — open a transaction, verify a signature, refund — and
 * the SDK would add a dependency plus static global configuration for that.
 * Laravel's HTTP client is easier to fake in tests and keeps the credentials
 * in config where the rest of the app expects them.
 */
final class MidtransGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'midtrans';
    }

    public function createTransaction(Payment $payment): PaymentSession
    {
        $order = $payment->order;
        $user = $order->user;

        // Snap rejects a duplicate order_id, and a client who lets a payment
        // expire and tries again needs a fresh one — hence the payment id
        // rather than the order number alone.
        $reference = $order->order_number.'-'.$payment->getKey();

        $response = $this->http()->post('/transactions', [
            'transaction_details' => [
                'order_id' => $reference,
                // Midtrans takes whole Rupiah only; the column is decimal(12,2)
                // and Indonesian prices have no cents.
                'gross_amount' => (int) round((float) $payment->amount),
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ],
            'item_details' => $order->items->map(fn ($item): array => [
                'id' => (string) $item->getKey(),
                'name' => mb_substr($item->name, 0, 50),
                'price' => (int) round((float) $item->unit_price),
                'quantity' => $item->quantity,
            ])->all(),
            'callbacks' => [
                'finish' => route('client.orders.show', $order),
            ],
            'expiry' => [
                'unit' => 'hour',
                'duration' => 24,
            ],
        ] + $this->enabledPayments());

        if ($response->failed()) {
            throw new RuntimeException(
                'Midtrans menolak transaksi: '.$response->body(),
            );
        }

        /** @var array<string, mixed> $body */
        $body = $response->json();

        return new PaymentSession(
            reference: $reference,
            redirectUrl: $body['redirect_url'] ?? null,
            token: $body['token'] ?? null,
            raw: $body,
        );
    }

    /**
     * Midtrans signs a notification with
     * sha512(order_id + status_code + gross_amount + server_key).
     */
    public function verifyWebhook(Request $request): bool
    {
        $serverKey = (string) config('payment.midtrans.server_key');
        $signature = (string) $request->input('signature_key');

        if ($serverKey === '' || $signature === '') {
            return false;
        }

        $expected = hash('sha512',
            (string) $request->input('order_id')
            .(string) $request->input('status_code')
            .(string) $request->input('gross_amount')
            .$serverKey,
        );

        return hash_equals($expected, $signature);
    }

    public function parseWebhook(Request $request): PaymentUpdate
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->all();

        return new PaymentUpdate(
            reference: (string) ($payload['order_id'] ?? ''),
            status: $this->mapStatus(
                (string) ($payload['transaction_status'] ?? ''),
                (string) ($payload['fraud_status'] ?? 'accept'),
            ),
            method: isset($payload['payment_type']) ? (string) $payload['payment_type'] : null,
            amount: isset($payload['gross_amount']) ? (float) $payload['gross_amount'] : null,
            paidAt: isset($payload['settlement_time'])
                ? (string) $payload['settlement_time']
                : (isset($payload['transaction_time']) ? (string) $payload['transaction_time'] : null),
            raw: $payload,
        );
    }

    public function refund(Payment $payment, ?float $amount = null): RefundResult
    {
        if ($payment->gateway_ref === null) {
            return RefundResult::failed('Pembayaran ini belum punya referensi gateway.');
        }

        try {
            $response = Http::withBasicAuth((string) config('payment.midtrans.server_key'), '')
                ->acceptJson()
                ->asJson()
                ->timeout((int) config('payment.midtrans.timeout', 15))
                ->baseUrl((string) config('payment.midtrans.api_url'))
                ->post("/{$payment->gateway_ref}/refund", array_filter([
                    'amount' => $amount === null ? null : (int) round($amount),
                    'reason' => 'Refund dari admin Undangyu',
                ]));
        } catch (ConnectionException $exception) {
            return RefundResult::failed($exception->getMessage());
        }

        /** @var array<string, mixed> $body */
        $body = $response->json() ?? [];

        if ($response->failed()) {
            return RefundResult::failed(
                (string) ($body['status_message'] ?? $response->body()),
                $body,
            );
        }

        return new RefundResult(
            successful: true,
            reference: isset($body['refund_key']) ? (string) $body['refund_key'] : null,
            amount: $amount ?? (float) $payment->amount,
            message: isset($body['status_message']) ? (string) $body['status_message'] : null,
            raw: $body,
        );
    }

    /**
     * Midtrans has more transaction states than the application needs, and
     * `capture` is only money in hand once fraud review accepts it.
     */
    private function mapStatus(string $transactionStatus, string $fraudStatus): PaymentStatus
    {
        return match ($transactionStatus) {
            'settlement' => PaymentStatus::Settled,
            'capture' => $fraudStatus === 'accept' ? PaymentStatus::Settled : PaymentStatus::Pending,
            'pending' => PaymentStatus::Pending,
            'deny', 'failure' => PaymentStatus::Failed,
            'cancel' => PaymentStatus::Failed,
            'expire' => PaymentStatus::Expired,
            'refund', 'partial_refund' => PaymentStatus::Refunded,
            default => PaymentStatus::Pending,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function enabledPayments(): array
    {
        /** @var list<string> $enabled */
        $enabled = config('payment.midtrans.enabled_payments', []);

        return $enabled === [] ? [] : ['enabled_payments' => $enabled];
    }

    private function http(): PendingRequest
    {
        $serverKey = (string) config('payment.midtrans.server_key');

        if ($serverKey === '') {
            throw new RuntimeException('MIDTRANS_SERVER_KEY belum diisi.');
        }

        return Http::withBasicAuth($serverKey, '')
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('payment.midtrans.timeout', 15))
            ->baseUrl((string) config('payment.midtrans.snap_url'));
    }
}
