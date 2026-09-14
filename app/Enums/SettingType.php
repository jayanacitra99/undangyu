<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How a setting's text `value` column is read back and written (docs/03 § 3.9).
 *
 * Everything is stored as text so one table holds every setting; this enum is
 * what turns "1" back into a boolean and "{...}" back into an array.
 */
enum SettingType: string
{
    case String = 'string';
    case Int = 'int';
    case Bool = 'bool';
    case Json = 'json';

    /**
     * The stored text as its real PHP type.
     */
    public function cast(?string $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($this) {
            self::String => $value,
            self::Int => (int) $value,
            self::Bool => filter_var($value, FILTER_VALIDATE_BOOL),
            self::Json => json_decode($value, true),
        };
    }

    /**
     * A PHP value as the text that goes in the column.
     */
    public function serialize(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($this) {
            self::String => (string) $value,
            self::Int => (string) (int) $value,
            self::Bool => filter_var($value, FILTER_VALIDATE_BOOL) ? '1' : '0',
            // Unescaped slashes keep mime types and URLs readable in the admin
            // textarea; the value round-trips either way.
            self::Json => json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        };
    }

    /**
     * The HTML input this type gets on the admin page.
     */
    public function inputType(): string
    {
        return match ($this) {
            self::String => 'text',
            self::Int => 'number',
            self::Bool => 'checkbox',
            self::Json => 'textarea',
        };
    }
}
