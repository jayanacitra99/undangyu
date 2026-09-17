<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Money, as strings.
 *
 * Every monetary column in this project is `decimal(12,2)` and Eloquent reads
 * those back as strings. CLAUDE.md says money is never a float, and the reason
 * is not pedantry: `0.1 + 0.2 !== 0.3` in binary floating point, and an order
 * total that is off by a hundredth of a Rupiah is an order total that does not
 * reconcile against what the gateway charged.
 *
 * So arithmetic here goes through bcmath, which works on decimal strings and
 * gives exact results. The one float cast in the whole money path is in
 * number(), where it exists only to hand `number_format()` what it wants for
 * display — nothing is computed from it.
 */
final class Money
{
    /**
     * Matches the scale of every money column in docs/03.
     */
    public const SCALE = 2;

    public const ZERO = '0.00';

    /**
     * `Rp 199.000` — Indonesian separators, no decimals. Rupiah has sen on
     * paper and nowhere in practice; prices here are whole.
     */
    public static function idr(string|int|null $amount): string
    {
        return 'Rp '.self::number($amount);
    }

    /**
     * The number alone, for a table column whose heading already says "Rp".
     */
    public static function number(string|int|null $amount): string
    {
        return number_format((float) self::normalise($amount), 0, ',', '.');
    }

    public static function add(string|int|null ...$amounts): string
    {
        $total = self::ZERO;

        foreach ($amounts as $amount) {
            $total = bcadd($total, self::normalise($amount), self::SCALE);
        }

        return $total;
    }

    public static function subtract(string|int|null $from, string|int|null ...$amounts): string
    {
        $total = self::normalise($from);

        foreach ($amounts as $amount) {
            $total = bcsub($total, self::normalise($amount), self::SCALE);
        }

        return $total;
    }

    /**
     * -1, 0 or 1 — the same shape as the spaceship operator, and the only way
     * two money values should ever be compared.
     */
    public static function compare(string|int|null $left, string|int|null $right): int
    {
        return bccomp(self::normalise($left), self::normalise($right), self::SCALE);
    }

    public static function equals(string|int|null $left, string|int|null $right): bool
    {
        return self::compare($left, $right) === 0;
    }

    public static function isPositive(string|int|null $amount): bool
    {
        return self::compare($amount, self::ZERO) > 0;
    }

    public static function isZero(string|int|null $amount): bool
    {
        return self::compare($amount, self::ZERO) === 0;
    }

    /**
     * Whole Rupiah, for gateways that will not take decimals — Midtrans's
     * `gross_amount` is the one that matters here.
     */
    public static function toWholeRupiah(string|int|null $amount): int
    {
        return (int) bcadd(self::normalise($amount), '0', 0);
    }

    /**
     * Anything that reaches this class becomes a well-formed decimal string.
     * A null is zero; an int is exact; a string is trusted but scaled, so
     * "199000" and "199000.00" compare equal.
     */
    private static function normalise(string|int|null $amount): string
    {
        if ($amount === null || $amount === '') {
            return self::ZERO;
        }

        return bcadd((string) $amount, '0', self::SCALE);
    }
}
