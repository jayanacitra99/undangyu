<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Formatting Rupiah for display.
 *
 * Money is stored and computed as `decimal(12,2)` and read back as a string;
 * this is the one place that turns such a string into something a human reads.
 * The float cast lives here and nowhere else — at four significant digits of
 * Rupiah it cannot lose anything, and confining it means no arithmetic
 * anywhere else is tempted to go through a float.
 */
final class Money
{
    /**
     * `Rp 199.000` — Indonesian separators, no decimals. Rupiah has sen on
     * paper and nowhere in practice; prices here are whole.
     */
    public static function idr(string|float|int|null $amount): string
    {
        return 'Rp '.self::number($amount);
    }

    /**
     * The number alone, for a table column that already says "Rp" in its head.
     */
    public static function number(string|float|int|null $amount): string
    {
        return number_format((float) ($amount ?? 0), 0, ',', '.');
    }
}
