<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The value must be a JSON object, not a scalar and not a list.
 *
 * `config_schema` and `default_config` are edited as raw JSON textareas in the
 * admin (M3.3). A valid-but-wrong-shape document — `[]`, `"x"`, `12` — would
 * pass Laravel's `json` rule and then break the builder, so the shape is
 * checked here before it can reach the column.
 */
final class JsonObject implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Kolom :attribute harus berupa teks JSON.')->translate();

            return;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $fail('Kolom :attribute bukan JSON yang valid: '.json_last_error_msg())->translate();

            return;
        }

        // An empty array decodes as a list, so `[]` and `{}` are both rejected
        // here — a schema with no keys is as useless as a JSON array.
        if (! is_array($decoded) || array_is_list($decoded)) {
            $fail('Kolom :attribute harus berupa objek JSON dengan minimal satu kunci.')->translate();
        }
    }
}
