<?php

declare(strict_types=1);

namespace App\Services\Wishes;

use App\Facades\Setting;

/**
 * Whether a guestbook message needs a human to look at it (M6.6, 29.2).
 *
 * Deliberately not a rejection. A word list has no idea what a sentence means,
 * and a wedding guestbook is exactly where an affectionate insult between
 * friends turns up — so a match holds the message for the client to release or
 * reject, and the client is the one who decides.
 *
 * Word boundaries, not substrings: "babi" must not flag "kebabi-babian", and
 * more to the point must not flag a guest whose name or village contains it.
 * Common letter-for-digit swaps are folded first, because the list is only
 * worth having if "b4ngs4t" reaches it too.
 */
final class ProfanityFilter
{
    /**
     * PHP casts the numeric keys below to integers; the type says so rather
     * than pretending they stay strings.
     *
     * @var array<int|string, string>
     */
    public const SUBSTITUTIONS = [
        '0' => 'o',
        '1' => 'i',
        '3' => 'e',
        '4' => 'a',
        '5' => 's',
        '7' => 't',
        '@' => 'a',
        '$' => 's',
    ];

    public function isClean(string $message): bool
    {
        return $this->matches($message) === [];
    }

    /**
     * Which listed words the message contains. Returned rather than counted,
     * so the moderation queue can tell the client why a message is waiting.
     *
     * @return list<string>
     */
    public function matches(string $message): array
    {
        $haystack = $this->normalize($message);

        $found = [];

        foreach ($this->blockedWords() as $word) {
            $needle = $this->normalize($word);

            if ($needle === '') {
                continue;
            }

            if (preg_match('/(?<![\p{L}\p{N}])'.preg_quote($needle, '/').'(?![\p{L}\p{N}])/u', $haystack) === 1) {
                $found[] = $word;
            }
        }

        return $found;
    }

    /**
     * @return list<string>
     */
    public function blockedWords(): array
    {
        /** @var list<string> $words */
        $words = Setting::get('wishes.blocked_words', []);

        return $words;
    }

    /**
     * Lowercase, digit-swaps folded, and repeated characters collapsed so
     * "anjiiiing" is the same word as "anjing".
     */
    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = strtr($value, self::SUBSTITUTIONS);

        return preg_replace('/(.)\1{2,}/u', '$1', $value) ?? $value;
    }
}
