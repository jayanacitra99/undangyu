<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\TemplateStatus;
use App\Rules\JsonObject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Everything the store and update requests agree on (M3.3).
 *
 * The two JSON columns are edited as raw textareas — a visual schema editor is
 * out of scope for this session — so they arrive as strings and are decoded in
 * payload() once they are known to be well-formed objects.
 */
abstract class TemplateRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'template_category_id' => ['required', 'integer', Rule::exists('template_categories', 'id')],
            'description' => ['nullable', 'string', 'max:2000'],
            'view_key' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9][a-z0-9\-]*$/'],
            'version' => ['required', 'string', 'max:20', 'regex:/^\d+\.\d+\.\d+$/'],
            'event_type_ids' => ['required', 'array', 'min:1'],
            'event_type_ids.*' => ['integer', Rule::exists('event_types', 'id')],
            'config_schema' => ['required', 'string', new JsonObject],
            'default_config' => ['required', 'string', new JsonObject],
            'demo_data' => ['nullable', 'string', new JsonObject],
            'is_premium' => ['required', 'boolean'],
            'extra_price' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'status' => ['required', Rule::enum(TemplateStatus::class)],
            'sort_order' => ['required', 'integer', 'min:0', 'max:32767'],

            'screenshots' => ['nullable', 'array', 'max:12'],
            'screenshots.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'screenshot_captions' => ['nullable', 'array'],
            'screenshot_captions.*' => ['nullable', 'string', 'max:160'],
            'remove_screenshots' => ['nullable', 'array'],
            'remove_screenshots.*' => ['integer'],
            'screenshot_order' => ['nullable', 'array'],
            'screenshot_order.*' => ['integer'],
        ];
    }

    /**
     * The validated attributes as the model wants them: JSON decoded, pivot and
     * upload keys removed.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->safe()->except([
            'event_type_ids',
            'thumbnail',
            'screenshots',
            'screenshot_captions',
            'remove_screenshots',
            'screenshot_order',
        ]);

        foreach (['config_schema', 'default_config', 'demo_data'] as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $data[$key] = is_string($data[$key]) && $data[$key] !== ''
                ? json_decode($data[$key], true)
                : null;
        }

        return $data;
    }

    /**
     * @return list<int>
     */
    public function eventTypeIds(): array
    {
        /** @var list<int> $ids */
        $ids = array_map(intval(...), $this->validated('event_type_ids', []));

        return array_values(array_unique($ids));
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'template_category_id.required' => 'Kategori wajib dipilih.',
            'view_key.required' => 'View key wajib diisi.',
            'view_key.regex' => 'View key hanya boleh huruf kecil, angka dan tanda hubung.',
            'version.regex' => 'Versi harus semver, misalnya 1.2.0.',
            'event_type_ids.required' => 'Pilih minimal satu jenis acara.',
            'thumbnail.required' => 'Thumbnail wajib diunggah.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_premium' => $this->boolean('is_premium'),
            'extra_price' => $this->input('extra_price') ?: 0,
            'sort_order' => $this->input('sort_order', 0),
            'demo_data' => $this->input('demo_data') ?: null,
        ]);
    }
}
