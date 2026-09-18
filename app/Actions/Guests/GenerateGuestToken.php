<?php

declare(strict_types=1);

namespace App\Actions\Guests;

use Illuminate\Support\Str;
use RuntimeException;

/**
 * The `?to=` value for one guest (24.2, hard rule 7).
 *
 * `budi-santoso-x7f2`: the slugified name so the link reads as the guest's
 * own, plus a random suffix so the list cannot be walked. Never the id, never
 * a counter — an enumerable token hands a stranger every guest's personalised
 * page, including the RSVP they can then edit.
 *
 * The suffix alphabet drops the characters that are misread when a client
 * dictates a link over the phone: 0/o, 1/l/i.
 */
final class GenerateGuestToken
{
    public const ALPHABET = '23456789abcdefghjkmnpqrstuvwxyz';

    public const SUFFIX_LENGTH = 4;

    /**
     * How many collisions to ride out before widening the suffix. Four
     * characters of this alphabet is ~923k combinations per name slug, so a
     * second attempt is already unlikely and a fourth means something is
     * wrong with the randomness rather than with the odds.
     */
    public const MAX_ATTEMPTS = 8;

    /**
     * @param  callable(string): bool  $exists  asks the database whether this token is taken
     */
    public function __invoke(string $name, callable $exists): string
    {
        $base = $this->base($name);

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            // Widen the suffix once the easy attempts are spent: retrying the
            // same width forever is how a saturated namespace becomes a loop.
            $length = self::SUFFIX_LENGTH + intdiv($attempt, 4);

            $token = $base.'-'.$this->suffix($length);

            if (! $exists($token)) {
                return $token;
            }
        }

        throw new RuntimeException('Could not generate a unique guest token for "'.$name.'".');
    }

    /**
     * The name part. Slugs can come back empty — a name written entirely in a
     * script Str::slug() strips leaves nothing — and an empty base would make
     * every such guest's token a bare suffix, so it falls back to a word.
     */
    private function base(string $name): string
    {
        $slug = Str::slug($name);

        if ($slug === '') {
            $slug = 'tamu';
        }

        // 80-char column, minus the suffix and its separator, with room for
        // the widened suffix the retry loop may reach for.
        return Str::limit($slug, 66, '');
    }

    private function suffix(int $length): string
    {
        $alphabet = self::ALPHABET;
        $max = strlen($alphabet) - 1;

        $suffix = '';

        for ($i = 0; $i < $length; $i++) {
            $suffix .= $alphabet[random_int(0, $max)];
        }

        return $suffix;
    }
}
