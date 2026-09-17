<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Records a bank transfer the client says they have made (M2.7).
 *
 * The proof goes to a private disk; nothing about a manual payment is
 * web-reachable. One pending attempt per order is reused rather than
 * duplicated — a client who uploads a clearer photo is replacing their proof,
 * not paying twice.
 */
final class SubmitManualPayment
{
    public function __invoke(Order $order, UploadedFile $proof): Payment
    {
        $path = $proof->store(Payment::PROOF_DIRECTORY, Payment::PROOF_DISK);

        $previousPath = null;

        $payment = DB::transaction(function () use ($order, $path, &$previousPath): Payment {
            $payment = $order->payments()
                ->manual()
                ->pending()
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($payment === null) {
                return $order->payments()->create([
                    'gateway' => Payment::GATEWAY_MANUAL,
                    'method' => 'bank_transfer',
                    'amount' => $order->total,
                    'status' => PaymentStatus::Pending,
                    'proof_path' => $path,
                ]);
            }

            $previousPath = $payment->proof_path;

            $payment->update([
                'amount' => $order->total,
                'proof_path' => $path,
                // A re-upload after a rejection is a fresh request to look.
                'verification_note' => null,
                'verified_by' => null,
                'verified_at' => null,
            ]);

            return $payment;
        });

        // Only once the row points at the new file.
        if ($previousPath !== null && $previousPath !== $path) {
            Storage::disk(Payment::PROOF_DISK)->delete($previousPath);
        }

        return $payment->refresh();
    }
}
