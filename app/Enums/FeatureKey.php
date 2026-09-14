<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Every capability a package can grant (docs/02 M2.2).
 *
 * Two kinds of key live here. A quota key holds a number and may be unlimited;
 * a switch key is on or off. The kind decides how the stored string is read
 * back, how the pricing table renders it, and what `check()` means for it —
 * nothing else in the system should be deciding that per call site.
 */
enum FeatureKey: string
{
    case MaxGuests = 'max_guests';
    case MaxPhotos = 'max_photos';
    case MaxVideoMb = 'max_video_mb';
    case WhatsappQuota = 'whatsapp_quota';
    case CustomDomain = 'custom_domain';
    case RemoveWatermark = 'remove_watermark';
    case Rsvp = 'rsvp';
    case Guestbook = 'guestbook';
    case Checkin = 'checkin';
    case Music = 'music';
    case LiveStream = 'live_stream';
    case Export = 'export';
    case AnalyticsAdvanced = 'analytics_advanced';

    /**
     * A quota is counted against; a switch is only ever on or off.
     */
    public function isQuota(): bool
    {
        return match ($this) {
            self::MaxGuests, self::MaxPhotos, self::MaxVideoMb, self::WhatsappQuota => true,
            default => false,
        };
    }

    /**
     * What a package that never mentions this key grants: nothing.
     */
    public function default(): int|bool
    {
        return $this->isQuota() ? 0 : false;
    }

    public function label(): string
    {
        return match ($this) {
            self::MaxGuests => 'Jumlah tamu',
            self::MaxPhotos => 'Jumlah foto',
            self::MaxVideoMb => 'Ukuran video (MB)',
            self::WhatsappQuota => 'Kuota WhatsApp',
            self::CustomDomain => 'Domain sendiri',
            self::RemoveWatermark => 'Tanpa watermark',
            self::Rsvp => 'RSVP',
            self::Guestbook => 'Buku tamu',
            self::Checkin => 'Check-in tamu',
            self::Music => 'Musik latar',
            self::LiveStream => 'Live streaming',
            self::Export => 'Ekspor data',
            self::AnalyticsAdvanced => 'Analitik lanjutan',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $key): string => $key->value, self::cases());
    }
}
