<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Actions\Invoices\GenerateInvoice;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Invoice download (M2.8).
 *
 * The route is signed, so a link cannot be guessed or kept forever, and it is
 * policy-gated, because a signature says a URL was issued by us — not that the
 * person holding it is allowed to read this one.
 */
final class InvoiceController extends Controller
{
    public function download(Invoice $invoice): StreamedResponse
    {
        Gate::authorize('download', $invoice);

        $disk = Storage::disk(GenerateInvoice::DISK);

        abort_unless($disk->exists((string) $invoice->pdf_path), 404);

        return $disk->download(
            (string) $invoice->pdf_path,
            str_replace('/', '-', $invoice->invoice_number).'.pdf',
        );
    }
}
