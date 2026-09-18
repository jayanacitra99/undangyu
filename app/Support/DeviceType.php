<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Which kind of device opened an invitation (M9.4).
 *
 * Three buckets and a null, from the user agent string. Deliberately crude:
 * the client's question is "are my guests on phones" — the answer to which is
 * yes, overwhelmingly — and the only decision it informs is whether the
 * template work stays mobile-first. A device-detection library would give a
 * more precise answer to a question nobody asked.
 */
final class DeviceType
{
    public const MOBILE = 'mobile';

    public const TABLET = 'tablet';

    public const DESKTOP = 'desktop';

    /**
     * @var list<string>
     */
    public const ALL = [self::MOBILE, self::TABLET, self::DESKTOP];

    public static function fromUserAgent(?string $userAgent): ?string
    {
        $agent = mb_strtolower(trim((string) $userAgent));

        if ($agent === '') {
            return null;
        }

        // Tablets first: an iPad's user agent contains neither "mobile" on
        // iPadOS nor anything else useful, and an Android tablet says
        // "android" without saying "mobile".
        if (str_contains($agent, 'ipad')
            || str_contains($agent, 'tablet')
            || (str_contains($agent, 'android') && ! str_contains($agent, 'mobile'))) {
            return self::TABLET;
        }

        if (str_contains($agent, 'mobile')
            || str_contains($agent, 'iphone')
            || str_contains($agent, 'android')
            || str_contains($agent, 'ipod')) {
            return self::MOBILE;
        }

        return self::DESKTOP;
    }

    public static function label(?string $device): string
    {
        return match ($device) {
            self::MOBILE => 'Ponsel',
            self::TABLET => 'Tablet',
            self::DESKTOP => 'Desktop',
            default => 'Tidak diketahui',
        };
    }
}
