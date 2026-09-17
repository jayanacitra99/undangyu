<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * What a piece of invitation media is (docs/03 § 3.4).
 *
 * The three types are quota-counted differently: photos against `max_photos`,
 * video against `max_video_mb`, audio against neither.
 */
enum MediaType: string
{
    case Image = 'image';
    case Video = 'video';
    case Audio = 'audio';

    public function label(): string
    {
        return match ($this) {
            self::Image => 'Foto',
            self::Video => 'Video',
            self::Audio => 'Audio',
        };
    }

    /**
     * Which entitlement this type is counted against, if any.
     */
    public function quotaKey(): ?FeatureKey
    {
        return match ($this) {
            self::Image => FeatureKey::MaxPhotos,
            self::Video => FeatureKey::MaxVideoMb,
            self::Audio => null,
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
