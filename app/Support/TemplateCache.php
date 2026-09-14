<?php

declare(strict_types=1);

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * The public template gallery is read on every marketing visit and changes only
 * when an admin edits the catalog, so it is cached for an hour and flushed by
 * the TemplateObserver instead of being left to expire.
 *
 * Everything goes under one tag, which keeps the flush a single call no matter
 * how many filter combinations are in the store.
 */
final class TemplateCache
{
    public const TAG = 'templates';

    public const TTL = 3600;

    public static function remember(string $key, Closure $callback): mixed
    {
        return self::store()->remember($key, self::TTL, $callback);
    }

    public static function flush(): void
    {
        self::store()->flush();
    }

    /**
     * A tagged repository where the store supports tags, the plain one where it
     * does not — the file and database stores cannot tag, and a local developer
     * running either should still get a working gallery.
     */
    private static function store(): mixed
    {
        $store = Cache::store();

        if (in_array(config('cache.default'), ['file', 'database', 'dynamodb'], true)) {
            return $store;
        }

        return $store->tags([self::TAG]);
    }
}
