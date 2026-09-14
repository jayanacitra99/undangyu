<?php

declare(strict_types=1);

namespace App\Facades;

use App\Enums\SettingType;
use App\Support\Settings;
use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed get(string $key, mixed $default = null)
 * @method static array<string, mixed> group(string $group)
 * @method static void set(string $key, mixed $value, ?SettingType $type = null, ?bool $isPublic = null)
 * @method static void setMany(array<string, mixed> $values)
 * @method static void forget(string $key)
 * @method static array<string, mixed> all()
 * @method static array<string, mixed> public()
 * @method static void flush()
 *
 * @see Settings
 */
class Setting extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Settings::class;
    }
}
