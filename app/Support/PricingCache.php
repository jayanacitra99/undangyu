<?php

declare(strict_types=1);

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * The public pricing table at /harga, cached the same way the template gallery
 * is: one tag, flushed by the package observers rather than left to expire.
 *
 * Only plain arrays go in — see TemplateCache for why models do not.
 */
final class PricingCache
{
    public const TAG = 'pricing';

    public const KEY = 'pricing:table';

    public const TTL = 3600;

    public static function remember(string $key, Closure $callback): mixed
    {
        return self::store()->remember($key, self::TTL, $callback);
    }

    public static function flush(): void
    {
        if (! self::taggable()) {
            // Flushing an untagged store would wipe every other cache entry, and
            // the pricing table is one known key.
            Cache::forget(self::KEY);

            return;
        }

        self::store()->flush();
    }

    private static function store(): mixed
    {
        return self::taggable()
            ? Cache::store()->tags([self::TAG])
            : Cache::store();
    }

    private static function taggable(): bool
    {
        return ! in_array(config('cache.default'), ['file', 'database', 'dynamodb'], true);
    }
}
