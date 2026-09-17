<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Coordinates out of a pasted Google Maps link (17.4).
 *
 * Clients paste whatever the Maps app gave them, which is one of half a dozen
 * shapes. This reads the ones that carry the numbers in the URL itself; a
 * short link (maps.app.goo.gl) carries nothing until it is followed, and
 * following it would mean an outbound request on a form save, so those fall
 * back to manual entry as 17.4 allows.
 *
 * No API key, no network. Purely a parser.
 */
final class MapsLink
{
    /**
     * Indonesia is roughly 6°N-11°S, 95°E-141°E, but the product is not the
     * border police: these are the bounds of the coordinate system itself, and
     * anything outside them is a parse that went wrong.
     */
    private const LAT_BOUND = 90.0;

    private const LNG_BOUND = 180.0;

    /**
     * @return array{latitude: string, longitude: string}|null
     */
    public static function coordinates(?string $url): ?array
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        foreach (self::patterns() as $pattern) {
            if (preg_match($pattern, $url, $matches) !== 1) {
                continue;
            }

            $pair = self::normalise($matches['lat'], $matches['lng']);

            if ($pair !== null) {
                return $pair;
            }
        }

        return null;
    }

    public static function isShortLink(?string $url): bool
    {
        if ($url === null) {
            return false;
        }

        return preg_match('#https?://(maps\.app\.goo\.gl|goo\.gl/maps)/#i', $url) === 1;
    }

    /**
     * Ordered most specific first. `!3d…!4d…` is the place marker's own
     * position and beats the `@…` viewport centre, which is only where the
     * camera happened to sit.
     *
     * @return list<string>
     */
    private static function patterns(): array
    {
        $number = '-?\d{1,3}(?:\.\d+)?';

        return [
            // .../data=!3m1!4b1!4m5!3m4!1s0x0:0x0!8m2!3d-6.2!4d106.8
            "#!3d(?<lat>{$number})!4d(?<lng>{$number})#",
            // /maps/@-6.2,106.8,17z  and  /maps/place/Name/@-6.2,106.8,17z
            "#/@(?<lat>{$number}),(?<lng>{$number})#",
            // ?q=-6.2,106.8  ·  ?query=-6.2,106.8  ·  ?ll=-6.2,106.8
            "#[?&](?:q|query|ll|center|daddr)=(?<lat>{$number}),\s*(?<lng>{$number})#",
        ];
    }

    /**
     * @return array{latitude: string, longitude: string}|null
     */
    private static function normalise(string $latitude, string $longitude): ?array
    {
        $lat = (float) $latitude;
        $lng = (float) $longitude;

        if (abs($lat) > self::LAT_BOUND || abs($lng) > self::LNG_BOUND) {
            return null;
        }

        // 0,0 is in the Atlantic. It is what a failed parse looks like, never
        // what a venue looks like.
        if ($lat === 0.0 && $lng === 0.0) {
            return null;
        }

        // decimal(10,7) is the column, so seven places is what fits.
        return [
            'latitude' => number_format($lat, 7, '.', ''),
            'longitude' => number_format($lng, 7, '.', ''),
        ];
    }
}
