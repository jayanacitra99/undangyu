<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How a message reaches a guest (docs/03 § 3.6).
 *
 * WhatsApp is the only channel that ships now — it is how Indonesian
 * invitations are actually sent — but the column and the enum carry the other
 * two, because a template is the same object whichever way it goes out.
 */
enum MessageChannel: string
{
    case Whatsapp = 'whatsapp';
    case Email = 'email';
    case Sms = 'sms';

    public function label(): string
    {
        return match ($this) {
            self::Whatsapp => 'WhatsApp',
            self::Email => 'Email',
            self::Sms => 'SMS',
        };
    }

    /**
     * Only email carries a subject; the others are a body and nothing else.
     */
    public function hasSubject(): bool
    {
        return $this === self::Email;
    }
}
