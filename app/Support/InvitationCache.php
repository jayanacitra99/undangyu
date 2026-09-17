<?php

declare(strict_types=1);

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * The cached public payload of one invitation (docs/05 § 7, hard rule 5).
 *
 * Everything belonging to an invitation goes under the tag `invitation:{id}`,
 * so an observer on any child model flushes the lot with one call. That is the
 * whole point of the tag: a client editing a venue must not leave guests
 * reading last week's address, and tracking which keys a change touched is how
 * that goes wrong.
 *
 * **Only plain arrays go in here.** A cached Eloquent model comes back from
 * phpredis as `__PHP_Incomplete_Class` when the class is not loaded in the
 * unserializing process — this bit us in Session 6, and a payload is data, not
 * objects.
 */
final class InvitationCache
{
    public const TTL = 3600;

    public static function tag(int $invitationId): string
    {
        return "invitation:{$invitationId}";
    }

    /**
     * @param  Closure(): array<string, mixed>  $callback
     * @return array<string, mixed>
     */
    public static function remember(int $invitationId, string $key, Closure $callback): array
    {
        /** @var array<string, mixed> $payload */
        $payload = self::store($invitationId)->remember($key, self::TTL, $callback);

        return $payload;
    }

    public static function forget(int $invitationId, string $key): void
    {
        self::store($invitationId)->forget($key);
    }

    public static function flush(int $invitationId): void
    {
        if (self::supportsTags()) {
            Cache::tags([self::tag($invitationId)])->flush();

            return;
        }

        // Untagged stores cannot flush a subset, and a stale invitation is a
        // worse bug than a cold cache. Same trade TemplateCache makes; the
        // stores that land here are local-development ones.
        Cache::flush();
    }

    private static function store(int $invitationId): mixed
    {
        $store = Cache::store();

        return self::supportsTags() ? $store->tags([self::tag($invitationId)]) : $store;
    }

    private static function supportsTags(): bool
    {
        return ! in_array(config('cache.default'), ['file', 'database', 'dynamodb'], true);
    }
}
