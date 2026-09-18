<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Indonesian mobile numbers, normalised to E.164 (M5.1).
 *
 * Clients type numbers four different ways — `0812…`, `62812…`, `+62 812-…`,
 * `(0812) …` — and all four have to become one string, because `wa.me/` takes
 * digits with no plus and a duplicate check on a guest list is a string
 * comparison. Normalising at the edge means nothing downstream has to know
 * that.
 *
 * Deliberately not a full libphonenumber: this is one country's mobile
 * numbering plan, and the cost of the library is not repaid by a rule that
 * fits in twenty lines.
 */
final class PhoneNumber
{
    public const COUNTRY_CODE = '62';

    /**
     * E.164 with the leading plus, or null if there is nothing usable.
     */
    public static function normalize(?string $input): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $input) ?? '';

        if ($digits === '') {
            return null;
        }

        // 0812… — the national form, which is how nearly everyone types it.
        if (str_starts_with($digits, '0')) {
            $digits = self::COUNTRY_CODE.ltrim($digits, '0');
        }

        // 812… — typed without either prefix. Anything already starting with a
        // country code is left alone, so a foreign guest's number survives.
        if (! str_starts_with($digits, self::COUNTRY_CODE) && strlen($digits) <= 12) {
            $digits = self::COUNTRY_CODE.$digits;
        }

        return '+'.$digits;
    }

    /**
     * Digits only, no plus — what `wa.me/{phone}` wants (M5.6).
     */
    public static function forWhatsApp(?string $input): ?string
    {
        $normalized = self::normalize($input);

        return $normalized === null ? null : ltrim($normalized, '+');
    }
}
