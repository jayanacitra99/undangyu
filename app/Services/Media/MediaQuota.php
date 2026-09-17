<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Enums\FeatureKey;
use App\Enums\MediaSource;
use App\Enums\MediaType;
use App\Models\Invitation;

/**
 * What an invitation has room for (18.2, hard rule 10).
 *
 * Counted against `invitations.entitlements` — the snapshot taken at
 * provisioning — never against what the package grants today. A client who
 * bought 30 photos keeps 30 photos when the package is repriced.
 *
 * Only uploads count. An embedded YouTube video costs no storage, and a track
 * from the curated library is one file shared by every invitation that picked
 * it, so neither is charged to a quota.
 */
final class MediaQuota
{
    public const BYTES_PER_MB = 1_048_576;

    public function photosUsed(Invitation $invitation): int
    {
        return $invitation->media()
            ->where('type', MediaType::Image)
            ->where('source', MediaSource::Upload)
            ->count();
    }

    public function videoMbUsed(Invitation $invitation): int
    {
        $bytes = (int) $invitation->media()
            ->where('type', MediaType::Video)
            ->where('source', MediaSource::Upload)
            ->sum('file_size');

        return (int) ceil($bytes / self::BYTES_PER_MB);
    }

    /**
     * The photo limit, or null for unlimited.
     */
    public function photoLimit(Invitation $invitation): ?int
    {
        return $this->limit($invitation, FeatureKey::MaxPhotos);
    }

    public function videoMbLimit(Invitation $invitation): ?int
    {
        return $this->limit($invitation, FeatureKey::MaxVideoMb);
    }

    /**
     * Is there room for one more photo?
     */
    public function hasPhotoRoom(Invitation $invitation): bool
    {
        $limit = $this->photoLimit($invitation);

        return $limit === null || $this->photosUsed($invitation) < $limit;
    }

    /**
     * Is there room for a video of this size? Checked before the file is
     * moved anywhere, so a refusal costs no storage.
     */
    public function hasVideoRoom(Invitation $invitation, int $bytes): bool
    {
        $limit = $this->videoMbLimit($invitation);

        if ($limit === null) {
            return true;
        }

        $incoming = (int) ceil($bytes / self::BYTES_PER_MB);

        return $this->videoMbUsed($invitation) + $incoming <= $limit;
    }

    /**
     * What the builder shows above the gallery, and what an over-quota
     * response says.
     *
     * @return array{photos: array{used: int, limit: int|null}, video_mb: array{used: int, limit: int|null}}
     */
    public function summary(Invitation $invitation): array
    {
        return [
            'photos' => [
                'used' => $this->photosUsed($invitation),
                'limit' => $this->photoLimit($invitation),
            ],
            'video_mb' => [
                'used' => $this->videoMbUsed($invitation),
                'limit' => $this->videoMbLimit($invitation),
            ],
        ];
    }

    /**
     * `null` means unlimited and is a real stored value, so a missing key
     * falls back to the feature's own default rather than to null.
     */
    private function limit(Invitation $invitation, FeatureKey $key): ?int
    {
        $value = $invitation->entitlement($key);

        if ($value === null) {
            return null;
        }

        return (int) $value;
    }
}
