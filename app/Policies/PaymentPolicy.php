<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

/**
 * Who may look at, and decide on, a payment (docs/04 § 11).
 *
 * `payments.verify` is the finance ability — admin holds it, support does not.
 * A client never reaches a payment record directly; they see their order.
 */
class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payments.verify') || $user->can('orders.viewAny');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->can('payments.verify')
            || $user->can('orders.viewAny')
            || $payment->order->user_id === $user->getKey();
    }

    /**
     * Approving releases an invitation and takes the money as final, so it is
     * gated tighter than merely reading the queue.
     */
    public function verify(User $user, Payment $payment): bool
    {
        return $user->can('payments.verify')
            && $payment->isManual()
            && ! $payment->status->isSettled();
    }

    /**
     * The proof is a private file. Reading it is the finance job, plus the
     * client who uploaded it.
     */
    public function viewProof(User $user, Payment $payment): bool
    {
        return $payment->proof_path !== null && $this->view($user, $payment);
    }
}
