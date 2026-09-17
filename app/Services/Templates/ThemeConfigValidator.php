<?php

declare(strict_types=1);

namespace App\Services\Templates;

use App\Models\Template;

/**
 * Checks a submitted theme against the template's own `config_schema` (19.4).
 *
 * The schema is the contract: it says which groups exist, which keys each one
 * has, and what a value may be. Anything else is rejected rather than stored —
 * `theme_config` is rendered into a published page, so an undeclared key is at
 * best dead weight in the payload and at worst something the renderer was
 * never written to handle.
 *
 * Shape (docs/05 § 5):
 *
 *   ['colors' => ['primary' => ['type' => 'color', 'default' => '#8B7355']]]
 *
 * Types understood here are the ones templates declare: color, select,
 * boolean, text and number. A type the schema uses but this does not know is
 * refused, loudly — silently accepting it would mean storing unvalidated input.
 */
final class ThemeConfigValidator
{
    public const HEX_COLOR = '/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/';

    /**
     * @param  array<string, mixed>  $submitted
     * @return array{config: array<string, array<string, mixed>>, errors: array<string, string>}
     */
    public function validate(Template $template, array $submitted): array
    {
        /** @var array<string, array<string, array<string, mixed>>> $schema */
        $schema = $template->config_schema ?? [];

        $config = [];
        $errors = [];

        foreach ($submitted as $group => $fields) {
            // A numeric key simply will not match a schema group, so
            // array_key_exists answers the "is this a real group" question on
            // its own.
            if (! array_key_exists($group, $schema) || ! is_array($fields)) {
                $errors["theme_config.{$group}"] = __('Kelompok pengaturan ini tidak ada pada tema.');

                continue;
            }

            foreach ($fields as $key => $value) {
                $field = $schema[$group][$key] ?? null;

                if (! is_string($key) || $field === null) {
                    $errors["theme_config.{$group}.{$key}"] = __('Pengaturan ini tidak ada pada tema.');

                    continue;
                }

                $checked = $this->value($field, $value);

                if ($checked['error'] !== null) {
                    $errors["theme_config.{$group}.{$key}"] = $checked['error'];

                    continue;
                }

                $config[$group][$key] = $checked['value'];
            }
        }

        return ['config' => $config, 'errors' => $errors];
    }

    /**
     * Every schema key at its declared default — what a client starts from and
     * what "reset" gives them back.
     *
     * @return array<string, array<string, mixed>>
     */
    public function defaults(Template $template): array
    {
        /** @var array<string, array<string, array<string, mixed>>> $schema */
        $schema = $template->config_schema ?? [];

        $defaults = [];

        foreach ($schema as $group => $fields) {
            foreach ($fields as $key => $field) {
                $defaults[$group][$key] = $field['default'] ?? null;
            }
        }

        return $defaults;
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array{value: mixed, error: string|null}
     */
    private function value(array $field, mixed $value): array
    {
        return match ($field['type'] ?? null) {
            'color' => $this->color($value),
            'select' => $this->select($field, $value),
            'boolean' => ['value' => (bool) $value, 'error' => null],
            'text' => $this->text($value),
            'number' => $this->number($field, $value),
            default => ['value' => null, 'error' => __('Jenis pengaturan ini tidak didukung.')],
        };
    }

    /**
     * @return array{value: mixed, error: string|null}
     */
    private function color(mixed $value): array
    {
        if (! is_string($value) || preg_match(self::HEX_COLOR, $value) !== 1) {
            return ['value' => null, 'error' => __('Warna harus berupa kode heksadesimal, misalnya #8B7355.')];
        }

        return ['value' => $value, 'error' => null];
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array{value: mixed, error: string|null}
     */
    private function select(array $field, mixed $value): array
    {
        /** @var list<string> $options */
        $options = $field['options'] ?? [];

        if (! is_string($value) || ! in_array($value, $options, true)) {
            return ['value' => null, 'error' => __('Pilihan ini tidak tersedia untuk tema ini.')];
        }

        return ['value' => $value, 'error' => null];
    }

    /**
     * @return array{value: mixed, error: string|null}
     */
    private function text(mixed $value): array
    {
        if (! is_string($value) || mb_strlen($value) > 200) {
            return ['value' => null, 'error' => __('Teks maksimal 200 karakter.')];
        }

        return ['value' => $value, 'error' => null];
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array{value: mixed, error: string|null}
     */
    private function number(array $field, mixed $value): array
    {
        if (! is_numeric($value)) {
            return ['value' => null, 'error' => __('Nilai harus berupa angka.')];
        }

        $number = (int) $value;
        $min = isset($field['min']) ? (int) $field['min'] : null;
        $max = isset($field['max']) ? (int) $field['max'] : null;

        if (($min !== null && $number < $min) || ($max !== null && $number > $max)) {
            return ['value' => null, 'error' => __('Nilai di luar rentang yang diizinkan tema.')];
        }

        return ['value' => $number, 'error' => null];
    }
}
