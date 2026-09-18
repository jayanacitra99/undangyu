<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * What a guest answered (docs/03 § 3.5, M6.1).
 *
 * `maybe` exists because Indonesian guests say "insyaAllah" and mean it — a
 * form that forces yes or no gets a yes from someone who has not decided, and
 * a catering count built on that is wrong in the expensive direction.
 */
enum RsvpAttendance: string
{
    case Yes = 'yes';
    case No = 'no';
    case Maybe = 'maybe';

    public function label(): string
    {
        return match ($this) {
            self::Yes => 'Hadir',
            self::No => 'Tidak hadir',
            self::Maybe => 'Masih ragu',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Yes => 'text-bg-success',
            self::No => 'text-bg-secondary',
            self::Maybe => 'text-bg-warning',
        };
    }

    /**
     * Whether this answer contributes people to the head count. `maybe` does
     * not: a client planning seats wants the number they can rely on, and the
     * dashboard shows the uncertain ones separately.
     */
    public function countsTowardPax(): bool
    {
        return $this === self::Yes;
    }
}
