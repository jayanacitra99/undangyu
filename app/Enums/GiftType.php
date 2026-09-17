<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How a guest can send a gift (docs/03 § 3.4).
 */
enum GiftType: string
{
    case Bank = 'bank';
    case Ewallet = 'ewallet';
    case Qris = 'qris';
    case Address = 'address';

    public function label(): string
    {
        return match ($this) {
            self::Bank => 'Transfer bank',
            self::Ewallet => 'Dompet digital',
            self::Qris => 'QRIS',
            self::Address => 'Kirim hadiah',
        };
    }

    /**
     * Does this type carry an account number to copy? `qris` is an image and
     * `address` is a postal address, so neither does.
     */
    public function hasAccountNumber(): bool
    {
        return in_array($this, [self::Bank, self::Ewallet], true);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
