<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Who may open a published invitation (docs/03 § 3.4).
 *
 * `unlisted` still renders for anyone holding the link — it only keeps the
 * invitation out of listings and search. `password` gates on the hashed
 * `invitations.password`.
 */
enum InvitationVisibility: string
{
    case Public = 'public';
    case Unlisted = 'unlisted';
    case Password = 'password';

    public function label(): string
    {
        return match ($this) {
            self::Public => 'Publik',
            self::Unlisted => 'Tidak terdaftar',
            self::Password => 'Dengan kata sandi',
        };
    }

    public function requiresPassword(): bool
    {
        return $this === self::Password;
    }

    /**
     * May search engines and public listings show it?
     */
    public function isIndexable(): bool
    {
        return $this === self::Public;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
