<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payment\Data\PaymentUpdate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * An admin's decision on a bank transfer (M2.7).
 *
 * Approval runs the *same* path a settled webhook takes — `ApplyPaymentUpdate`
 * — so the order transition and the provisioning dispatch exist in one place
 * and cannot drift apart. This class only records who decided, and why.
 */
final class VerifyManualPayment
{
    public function __construct(private readonly ApplyPaymentUpdate $applyUpdate) {}

    public function approve(Payment $payment, User $verifier, ?string $note = null): bool
    {
        if (! $payment->isManual()) {
            return false;
        }

        // A manual payment has no gateway reference of its own, and
        // ApplyPaymentUpdate finds rows by exactly that. Minting one here also
        // means the approval is idempotent for the same reason a webhook is:
        // the unique index refuses a second.
        $reference = $payment->gateway_ref ?? $this->reference($payment);

        DB::transaction(function () use ($payment, $verifier, $note, $reference): void {
            $payment->forceFill([
                'gateway_ref' => $reference,
                'verified_by' => $verifier->getKey(),
                'verification_note' => $note,
                'verified_at' => now(),
            ])->save();
        });

        $applied = ($this->applyUpdate)(new PaymentUpdate(
            reference: $reference,
            status: PaymentStatus::Settled,
            method: $payment->method ?? 'bank_transfer',
            amount: (float) $payment->amount,
            paidAt: now()->toIso8601String(),
            raw: [
                'source' => 'manual_verification',
                'verified_by' => $verifier->getKey(),
                'note' => $note,
            ],
        ), Payment::GATEWAY_MANUAL);

        Log::info('Transfer manual disetujui.', [
            'payment' => $payment->getKey(),
            'order' => $payment->order->order_number,
            'verifier' => $verifier->getKey(),
        ]);

        return $applied;
    }

    /**
     * Rejection leaves the order alone: it is still unpaid, still expiring on
     * its own deadline. The client can upload a better proof.
     */
    public function reject(Payment $payment, User $verifier, string $note): bool
    {
        if (! $payment->isManual() || $payment->status->isSettled()) {
            return false;
        }

        $payment->forceFill([
            'status' => PaymentStatus::Failed,
            'verified_by' => $verifier->getKey(),
            'verification_note' => $note,
            'verified_at' => now(),
        ])->save();

        Log::info('Transfer manual ditolak.', [
            'payment' => $payment->getKey(),
            'order' => $payment->order->order_number,
            'verifier' => $verifier->getKey(),
        ]);

        return true;
    }

    private function reference(Payment $payment): string
    {
        return 'MANUAL-'.$payment->order->order_number.'-'.$payment->getKey();
    }
}
