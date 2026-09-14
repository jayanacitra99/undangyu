<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\FeatureKey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Everything the store and update requests agree on (playbook 7.4).
 *
 * The feature editor posts a repeatable row set: `features[n][feature_key]`,
 * `[feature_value]`, `[is_unlimited]`. A quota row needs a number unless it is
 * unlimited; a switch row is the checkbox alone.
 */
abstract class PackageRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'discount_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999', 'lt:price'],
            'currency' => ['required', 'string', 'size:3'],
            'active_days' => ['required', 'integer', 'min:1', 'max:32767'],
            'is_featured' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:32767'],

            'features' => ['nullable', 'array', 'max:'.count(FeatureKey::cases())],
            'features.*.feature_key' => ['required', Rule::enum(FeatureKey::class)],
            'features.*.feature_value' => ['nullable', 'string', 'max:100'],
            'features.*.is_unlimited' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $seen = [];

            foreach ((array) $this->input('features', []) as $index => $feature) {
                $key = FeatureKey::tryFrom((string) ($feature['feature_key'] ?? ''));

                if ($key === null) {
                    continue;
                }

                if (isset($seen[$key->value])) {
                    $validator->errors()->add(
                        "features.{$index}.feature_key",
                        "Fitur {$key->label()} sudah ada di paket ini.",
                    );

                    continue;
                }

                $seen[$key->value] = true;

                if (! $key->isQuota()) {
                    continue;
                }

                $unlimited = filter_var($feature['is_unlimited'] ?? false, FILTER_VALIDATE_BOOL);
                $value = $feature['feature_value'] ?? null;

                if (! $unlimited && ! is_numeric($value)) {
                    $validator->errors()->add(
                        "features.{$index}.feature_value",
                        "Fitur {$key->label()} butuh angka atau centang tanpa batas.",
                    );
                }
            }
        });
    }

    /**
     * The validated attributes the model wants — the feature rows removed.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->safe()->except('features');

        $data['slug'] = $this->resolvedSlug();
        $data['discount_price'] = $data['discount_price'] ?? null;

        return $data;
    }

    /**
     * The feature rows, normalised: a switch carries "true"/"false", a quota
     * carries its number or nothing when it is unlimited.
     *
     * @return list<array{feature_key: string, feature_value: ?string, is_unlimited: bool}>
     */
    public function featureRows(): array
    {
        $rows = [];

        foreach ((array) $this->validated('features', []) as $feature) {
            $key = FeatureKey::from($feature['feature_key']);
            $unlimited = (bool) $feature['is_unlimited'];

            if ($key->isQuota()) {
                $rows[] = [
                    'feature_key' => $key->value,
                    'feature_value' => $unlimited ? null : (string) (int) $feature['feature_value'],
                    'is_unlimited' => $unlimited,
                ];

                continue;
            }

            $rows[] = [
                'feature_key' => $key->value,
                'feature_value' => $unlimited ? 'true' : 'false',
                'is_unlimited' => false,
            ];
        }

        return $rows;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'price.required' => 'Harga wajib diisi.',
            'discount_price.lt' => 'Harga diskon harus lebih kecil dari harga normal.',
            'active_days.required' => 'Masa aktif wajib diisi.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $features = [];

        foreach ((array) $this->input('features', []) as $index => $feature) {
            $features[$index] = [
                'feature_key' => $feature['feature_key'] ?? null,
                'feature_value' => $feature['feature_value'] ?? null,
                'is_unlimited' => filter_var($feature['is_unlimited'] ?? false, FILTER_VALIDATE_BOOL),
            ];
        }

        $this->merge([
            'currency' => Str::upper((string) ($this->input('currency') ?: 'IDR')),
            'discount_price' => $this->input('discount_price') === '' ? null : $this->input('discount_price'),
            'is_featured' => $this->boolean('is_featured'),
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $this->input('sort_order', 0),
            'features' => $features,
        ]);
    }

    abstract protected function resolvedSlug(): string;
}
