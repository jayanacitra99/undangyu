<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

/**
 * Who may read an invoice (M2.8).
 *
 * The client it was issued to, and staff who manage orders. The signed URL on
 * the download route bounds how long a link survives; this decides who the
 * link works for at all — a signature is not authorization.
 */
class InvoicePolicy
{
    public function view(User $user, Invoice $invoice): bool
    {
        return $invoice->order->user_id === $user->getKey()
            || $user->can('orders.viewAny')
            || $user->can('payments.verify');
    }

    /**
     * Downloading needs the document to exist as well as the right to see it.
     */
    public function download(User $user, Invoice $invoice): bool
    {
        return $invoice->pdf_path !== null && $this->view($user, $invoice);
    }
}
