<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where a guestbook message sits (docs/03 § 3.5, M6.5).
 *
 * The invitation's `guestbook_moderation` setting decides which status a new
 * wish is born with: `auto` publishes immediately, `manual` holds it for the
 * client. Rejected messages are kept rather than deleted — a client who
 * rejects the wrong one has to be able to change their mind.
 */
enum WishStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Approved => 'Tampil',
            self::Rejected => 'Ditolak',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'text-bg-warning',
            self::Approved => 'text-bg-success',
            self::Rejected => 'text-bg-secondary',
        };
    }

    /**
     * Only approved wishes are ever served to guests.
     */
    public function isPublic(): bool
    {
        return $this === self::Approved;
    }
}
