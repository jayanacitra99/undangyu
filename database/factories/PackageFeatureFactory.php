<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FeatureKey;
use App\Models\Package;
use App\Models\PackageFeature;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PackageFeature>
 */
class PackageFeatureFactory extends Factory
{
    protected $model = PackageFeature::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $key = fake()->randomElement(FeatureKey::cases());

        return [
            'package_id' => Package::factory(),
            'feature_key' => $key->value,
            'feature_value' => $key->isQuota() ? (string) fake()->numberBetween(50, 1000) : 'true',
            'is_unlimited' => false,
        ];
    }

    public function unlimited(): self
    {
        return $this->state(fn (): array => [
            'feature_value' => null,
            'is_unlimited' => true,
        ]);
    }

    public function withKey(FeatureKey $key, int|bool|null $value = null): self
    {
        return $this->state(fn (): array => [
            'feature_key' => $key->value,
            'feature_value' => match (true) {
                $value === null => null,
                $key->isQuota() => (string) (int) $value,
                default => $value ? 'true' : 'false',
            },
            'is_unlimited' => $key->isQuota() && $value === null,
        ]);
    }
}
