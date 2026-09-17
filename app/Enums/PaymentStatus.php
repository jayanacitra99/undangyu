<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where one payment attempt stands (docs/03 § 3.2).
 *
 * An order can carry several payments — a failed card, then a settled VA — so
 * this is the status of the attempt, never of the order.
 */
enum PaymentStatus: string
{
    case Pending = 'pending';
    case Settled = 'settled';
    case Failed = 'failed';
    case Expired = 'expired';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Settled => 'Lunas',
            self::Failed => 'Gagal',
            self::Expired => 'Kedaluwarsa',
            self::Refunded => 'Dikembalikan',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'text-bg-warning',
            self::Settled => 'text-bg-success',
            self::Failed => 'text-bg-danger',
            self::Expired => 'text-bg-secondary',
            self::Refunded => 'text-bg-dark',
        };
    }

    /**
     * The money arrived. Webhook handling in Session 10 treats this as the one
     * state that may provision an invitation.
     */
    public function isSettled(): bool
    {
        return $this === self::Settled;
    }

    /**
     * Nothing more will happen to this attempt.
     */
    public function isFinal(): bool
    {
        return $this !== self::Pending;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status): string => $status->value, self::cases());
    }
}
