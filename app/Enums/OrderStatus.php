<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where an order sits in its lifecycle (docs/02 M2.9).
 *
 * `pending -> paid -> provisioned` is the happy path. `expired` is what the
 * hourly sweep writes; `cancelled` and `refunded` are human decisions.
 */
enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Provisioned = 'provisioned';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu pembayaran',
            self::Paid => 'Dibayar',
            self::Provisioned => 'Aktif',
            self::Expired => 'Kedaluwarsa',
            self::Cancelled => 'Dibatalkan',
            self::Refunded => 'Dikembalikan',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'text-bg-warning',
            self::Paid => 'text-bg-info',
            self::Provisioned => 'text-bg-success',
            self::Expired => 'text-bg-secondary',
            self::Cancelled => 'text-bg-dark',
            self::Refunded => 'text-bg-danger',
        };
    }

    /**
     * Is this order still waiting for money? Only a pending order can be paid,
     * expired or cancelled by the client.
     */
    public function isOpen(): bool
    {
        return $this === self::Pending;
    }

    /**
     * Has the client paid? Both states mean the money arrived — `provisioned`
     * only adds that the invitation now exists.
     */
    public function isSettled(): bool
    {
        return in_array($this, [self::Paid, self::Provisioned], true);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status): string => $status->value, self::cases());
    }
}
