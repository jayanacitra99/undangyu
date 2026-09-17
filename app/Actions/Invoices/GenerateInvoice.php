<?php

declare(strict_types=1);

namespace App\Actions\Invoices;

use App\Facades\Setting;
use App\Models\Invoice;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Renders the invoice for a paid order and stores it privately (M2.8).
 *
 * Idempotent on the order: one invoice, one number, however often the job runs.
 * A re-render replaces the PDF but keeps the number — reissuing a document with
 * a new number for the same sale is what accountants call a problem.
 */
final class GenerateInvoice
{
    public const DISK = 'local';

    public const DIRECTORY = 'invoices';

    /**
     * Two jobs can read the same highest number before either inserts; the
     * unique index refuses the loser, which then reads again.
     */
    private const MAX_ATTEMPTS = 5;

    public function __construct(private readonly GenerateInvoiceNumber $generateNumber) {}

    public function __invoke(Order $order): Invoice
    {
        $invoice = $this->record($order);

        $order->loadMissing(['items', 'user', 'package', 'payments']);

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'order' => $order,
            // The payment that actually settled, for "paid by" on the document.
            'payment' => $order->payments->firstWhere(fn ($payment): bool => $payment->status->isSettled()),
            'company' => [
                'legal_name' => Setting::get('site.legal_name', Setting::get('site.name')),
                'address' => Setting::get('site.address'),
                'tax_id' => Setting::get('site.tax_id'),
                'email' => Setting::get('site.contact_email'),
                'whatsapp' => Setting::get('site.whatsapp_number'),
            ],
        ])->setPaper('a4');

        $path = self::DIRECTORY.'/'.str_replace('/', '-', $invoice->invoice_number).'.pdf';

        Storage::disk(self::DISK)->put($path, $pdf->output());

        $invoice->update([
            'pdf_path' => $path,
            'issued_at' => $invoice->issued_at ?? now(),
        ]);

        return $invoice->refresh();
    }

    /**
     * The invoice row, created once and reused thereafter.
     */
    private function record(Order $order): Invoice
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return DB::transaction(function () use ($order): Invoice {
                    // Lock the order first. A plain read would let two
                    // overlapping renders each take their snapshot before the
                    // other inserted, and both would see no invoice.
                    Order::query()->whereKey($order->getKey())->lockForUpdate()->first();

                    $existing = Invoice::query()->where('order_id', $order->getKey())->first();

                    if ($existing !== null) {
                        return $existing;
                    }

                    return Invoice::query()->create([
                        'order_id' => $order->getKey(),
                        'invoice_number' => ($this->generateNumber)(),
                        'issued_at' => now(),
                        // Thirty days is the accounting convention; the order's
                        // own payment deadline has already passed by now.
                        'due_at' => now()->addDays(30),
                    ]);
                });
            } catch (QueryException $exception) {
                if ($attempt >= self::MAX_ATTEMPTS || ! $this->isDuplicate($exception)) {
                    throw $exception;
                }
            }
        }
    }

    /**
     * Both constraints exist: `invoice_number` unique from the original
     * migration, `order_id` unique added at the Phase 2 checkpoint. A collision
     * on either means another run committed first, so read again.
     */
    private function isDuplicate(QueryException $exception): bool
    {
        return $exception->getCode() === '23000'
            && (str_contains($exception->getMessage(), 'invoice_number')
                || str_contains($exception->getMessage(), 'order_id'));
    }
}
