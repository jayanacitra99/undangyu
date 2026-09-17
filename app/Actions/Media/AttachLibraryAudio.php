<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Enums\MediaSource;
use App\Enums\MediaType;
use App\Facades\Setting;
use App\Models\Invitation;
use App\Models\InvitationMedia;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Picks a track from the curated library (18.6, M4.8).
 *
 * The library is a seeded setting, so adding a track is an admin edit rather
 * than a deploy, and licensing is answered once for everyone instead of per
 * client upload.
 *
 * One invitation has one background track: picking a second replaces the
 * first, which is what the single "musik latar" control means.
 */
final class AttachLibraryAudio
{
    public const SETTING_KEY = 'media.audio_library';

    public function __invoke(Invitation $invitation, string $trackKey): InvitationMedia
    {
        $track = self::track($trackKey);

        if ($track === null) {
            throw new RuntimeException('Lagu tidak ada dalam pustaka.');
        }

        return DB::transaction(function () use ($invitation, $track): InvitationMedia {
            $invitation->media()->where('type', MediaType::Audio)->delete();

            return $invitation->media()->create([
                'type' => MediaType::Audio,
                'source' => MediaSource::Library,
                // The library lives on the public disk: it is our own file,
                // identical for every invitation, and nothing about it is
                // private.
                'disk' => 'public',
                'path' => $track['path'],
                'caption' => $track['title'],
                'file_size' => $track['file_size'] ?? null,
                'is_cover' => false,
                'sort_order' => ((int) ($invitation->media()->max('sort_order') ?? -1)) + 1,
            ]);
        });
    }

    /**
     * @return list<array{key: string, title: string, artist: string|null, path: string, file_size: int|null}>
     */
    public static function library(): array
    {
        /** @var list<array{key: string, title: string, artist: string|null, path: string, file_size: int|null}> $tracks */
        $tracks = Setting::get(self::SETTING_KEY, []);

        return $tracks;
    }

    /**
     * @return array{key: string, title: string, artist: string|null, path: string, file_size: int|null}|null
     */
    public static function track(string $key): ?array
    {
        foreach (self::library() as $track) {
            if ($track['key'] === $key) {
                return $track;
            }
        }

        return null;
    }
}
