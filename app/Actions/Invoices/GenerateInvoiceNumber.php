<?php

declare(strict_types=1);

namespace App\Actions\Invoices;

use App\Models\Invoice;
use Illuminate\Support\Carbon;

/**
 * The next `INV/2026/000001` (M2.8).
 *
 * Sequential within the year and gapless: accountants read a missing number as
 * a deleted invoice. The sequence is derived under `lockForUpdate`, and the
 * unique index on `invoices.invoice_number` is what actually guarantees it —
 * GenerateInvoice retries when two jobs race.
 */
final class GenerateInvoiceNumber
{
    public const PREFIX = 'INV';

    public const PAD = 6;

    public function __invoke(?Carbon $date = null): string
    {
        $date ??= now();
        $prefix = self::PREFIX.'/'.$date->format('Y').'/';

        $highest = Invoice::query()
            ->where('invoice_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->max('invoice_number');

        $sequence = $highest === null
            ? 0
            : (int) substr((string) $highest, strlen($prefix));

        return $prefix.str_pad((string) ($sequence + 1), self::PAD, '0', STR_PAD_LEFT);
    }
}
