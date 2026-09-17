<?php

declare(strict_types=1);

namespace App\Support;

/**
 * YouTube and Vimeo links, reduced to an id (18.5).
 *
 * Clients paste the share link, the watch URL, the mobile URL or the embed
 * iframe's src; all four carry the same id in a different place. Nothing here
 * calls an API — the id is in the URL, and a thumbnail for YouTube is a
 * predictable path built from it.
 *
 * Vimeo's thumbnail is not predictable and needs oEmbed, which is a network
 * call on a form save. So Vimeo returns a null thumbnail and the gallery draws
 * a placeholder.
 */
final class EmbedLink
{
    public const YOUTUBE = 'youtube';

    public const VIMEO = 'vimeo';

    /**
     * @return array{provider: string, id: string, embed_url: string, thumbnail: string|null}|null
     */
    public static function parse(?string $url): ?array
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        $youtube = self::match($url, [
            // youtu.be/ID · youtube.com/watch?v=ID · /embed/ID · /shorts/ID · /live/ID
            '#youtu\.be/(?<id>[A-Za-z0-9_-]{11})#',
            '#youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/)(?<id>[A-Za-z0-9_-]{11})#',
        ]);

        if ($youtube !== null) {
            return [
                'provider' => self::YOUTUBE,
                'id' => $youtube,
                'embed_url' => "https://www.youtube.com/embed/{$youtube}",
                'thumbnail' => "https://i.ytimg.com/vi/{$youtube}/hqdefault.jpg",
            ];
        }

        $vimeo = self::match($url, [
            '#vimeo\.com/(?:video/)?(?<id>\d{6,12})#',
            '#player\.vimeo\.com/video/(?<id>\d{6,12})#',
        ]);

        if ($vimeo !== null) {
            return [
                'provider' => self::VIMEO,
                'id' => $vimeo,
                'embed_url' => "https://player.vimeo.com/video/{$vimeo}",
                // Vimeo's thumbnail needs an oEmbed call, which is not worth an
                // outbound request on a save. The gallery draws a placeholder.
                'thumbnail' => null,
            ];
        }

        return null;
    }

    public static function isSupported(?string $url): bool
    {
        return self::parse($url) !== null;
    }

    /**
     * @param  list<string>  $patterns
     */
    private static function match(string $url, array $patterns): ?string
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches) === 1) {
                return $matches['id'];
            }
        }

        return null;
    }
}
