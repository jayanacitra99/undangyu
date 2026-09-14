<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\SettingType;
use App\Models\Setting;
use App\Rules\JsonObject;
use App\Support\Settings;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Validates a settings save against the rows that actually exist.
 *
 * Rules come from each row's `type`, so a new setting is validated correctly the
 * moment it is seeded — nothing to add here. Keys with no row are ignored rather
 * than created: the admin page edits settings, it does not invent them.
 */
class UpdateSettingsRequest extends FormRequest
{
    /**
     * @var Collection<string, Setting>|null
     */
    private ?Collection $settings = null;

    public function authorize(): bool
    {
        return $this->user()?->can('settings.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'settings' => ['required', 'array'],
            // Echoed back into the redirect URL, so it is validated input, not
            // a free-form string the controller reads off the request.
            'tab' => ['nullable', 'string', Rule::in(Settings::GROUP_ORDER)],
        ];

        foreach ($this->editableSettings() as $key => $setting) {
            $rules["settings.{$key}"] = match ($setting->type) {
                SettingType::Int => ['nullable', 'integer', 'min:0'],
                SettingType::Bool => ['nullable', 'boolean'],
                // `json` alone accepts "5" and "\"x\"", which then decode into
                // the column as a scalar. A JSON setting is an object, and the
                // text column caps at 65,535 bytes.
                SettingType::Json => ['nullable', 'string', 'max:60000', new JsonObject(allowList: true)],
                SettingType::String => ['nullable', 'string', 'max:1000'],
            };
        }

        return $rules;
    }

    /**
     * The admin panel is Indonesian; the default validator messages are not.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'integer' => ':attribute harus berupa angka.',
            'min' => ':attribute tidak boleh kurang dari :min.',
            'json' => ':attribute harus berupa JSON yang valid.',
            'boolean' => ':attribute harus berupa ya atau tidak.',
            'string' => ':attribute harus berupa teks.',
            'max' => ':attribute maksimal :max karakter.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [];

        foreach ($this->editableSettings() as $key => $setting) {
            $attributes["settings.{$key}"] = str_replace('_', ' ', $setting->key);
        }

        return $attributes;
    }

    /**
     * The submitted values keyed "group.key", ready for the store.
     *
     * Checkboxes send nothing when unchecked, so every boolean in the posted
     * groups is resolved here rather than left missing.
     *
     * @return array<string, mixed>
     */
    public function settingsToSave(): array
    {
        /** @var array<string, array<string, mixed>> $submitted */
        $submitted = $this->validated('settings', []);

        $values = [];

        foreach ($this->editableSettings() as $key => $setting) {
            [$group, $shortKey] = explode('.', $key, 2);

            // Only touch the groups this form actually showed.
            if (! array_key_exists($group, $submitted)) {
                continue;
            }

            $value = $submitted[$group][$shortKey] ?? null;

            $values[$key] = match ($setting->type) {
                SettingType::Bool => (bool) $value,
                SettingType::Json => $value === null ? null : json_decode($value, true),
                SettingType::Int => $value === null ? null : (int) $value,
                SettingType::String => $value,
            };
        }

        return $values;
    }

    protected function prepareForValidation(): void
    {
        /** @var array<string, array<string, mixed>> $submitted */
        $submitted = (array) $this->input('settings', []);

        // A missing checkbox means false, not "leave as is".
        foreach ($this->editableSettings() as $key => $setting) {
            if ($setting->type !== SettingType::Bool) {
                continue;
            }

            [$group, $shortKey] = explode('.', $key, 2);

            if (array_key_exists($group, $submitted)) {
                $submitted[$group][$shortKey] = (bool) ($submitted[$group][$shortKey] ?? false);
            }
        }

        $this->merge(['settings' => $submitted]);
    }

    protected function failedValidation(Validator $validator): void
    {
        // Send the admin back to the tab they were on.
        $this->merge(['tab' => $this->input('tab')]);

        parent::failedValidation($validator);
    }

    /**
     * @return Collection<string, Setting>
     */
    private function editableSettings(): Collection
    {
        return $this->settings ??= Setting::query()
            ->get()
            ->keyBy(fn (Setting $setting): string => "{$setting->group}.{$setting->key}");
    }
}
