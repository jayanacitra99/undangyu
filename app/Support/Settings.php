<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\SettingType;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * The global settings store (docs/05 § 7: Redis, forever, flushed on write).
 *
 * One cache entry holds every setting, keyed "group.key". Settings are read on
 * nearly every request and written a few times a year, so a single round trip
 * beats a key per setting, and a write simply drops the whole entry.
 */
final class Settings
{
    public const CACHE_KEY = 'settings:all';

    /**
     * The order groups appear in on the admin page. Alphabetical would open on
     * "invitation"; an admin coming to this screen wants "site" first. Groups
     * not listed here sort after these, alphabetically.
     *
     * @var list<string>
     */
    public const GROUP_ORDER = ['site', 'seo', 'invitation', 'media', 'payment'];

    /**
     * @var array<string, mixed>|null
     */
    private ?array $memo = null;

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /**
     * Every setting in one group, keyed by its short key.
     *
     * @return array<string, mixed>
     */
    public function group(string $group): array
    {
        $values = [];

        foreach ($this->all() as $key => $value) {
            if (str_starts_with($key, "{$group}.")) {
                $values[substr($key, strlen($group) + 1)] = $value;
            }
        }

        return $values;
    }

    /**
     * Write one setting and drop the cache.
     *
     * The type of an existing row wins — a caller passing a string for an int
     * setting gets it stored as an int, not silently retyped.
     */
    public function set(string $key, mixed $value, ?SettingType $type = null, ?bool $isPublic = null): void
    {
        [$group, $shortKey] = $this->split($key);

        $setting = Setting::query()->firstOrNew([
            'group' => $group,
            'key' => $shortKey,
        ]);

        $setting->type = $type ?? $setting->type ?? $this->inferType($value);
        $setting->value = $setting->type->serialize($value);

        if ($isPublic !== null) {
            $setting->is_public = $isPublic;
        }

        $setting->save();

        $this->flush();
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function forget(string $key): void
    {
        [$group, $shortKey] = $this->split($key);

        Setting::query()->where('group', $group)->where('key', $shortKey)->delete();

        $this->flush();
    }

    /**
     * Every setting, keyed "group.key", cast by its type.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->memo ??= Cache::rememberForever(
            self::CACHE_KEY,
            fn (): array => Setting::query()
                ->get()
                ->mapWithKeys(fn (Setting $setting): array => [
                    "{$setting->group}.{$setting->key}" => $setting->typedValue(),
                ])
                ->all(),
        );
    }

    /**
     * Only the settings an invitation page may expose.
     *
     * @return array<string, mixed>
     */
    public function public(): array
    {
        return Setting::query()
            ->where('is_public', true)
            ->get()
            ->mapWithKeys(fn (Setting $setting): array => [
                "{$setting->group}.{$setting->key}" => $setting->typedValue(),
            ])
            ->all();
    }

    public function flush(): void
    {
        $this->memo = null;

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function split(string $key): array
    {
        if (! str_contains($key, '.')) {
            throw new \InvalidArgumentException("Setting key [{$key}] must be prefixed with its group, e.g. site.name.");
        }

        [$group, $shortKey] = explode('.', $key, 2);

        return [$group, $shortKey];
    }

    private function inferType(mixed $value): SettingType
    {
        return match (true) {
            is_bool($value) => SettingType::Bool,
            is_int($value) => SettingType::Int,
            is_array($value) => SettingType::Json,
            default => SettingType::String,
        };
    }
}
