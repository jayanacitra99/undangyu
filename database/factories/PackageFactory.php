<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FeatureKey;
use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    protected $model = Package::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement(['Free', 'Basic', 'Premium', 'Exclusive']).' '.fake()->unique()->numberBetween(1, 99999);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => 'Paket undangan digital dengan fitur standar.',
            'price' => fake()->randomElement([0, 99000, 199000, 399000]),
            'discount_price' => null,
            'currency' => 'IDR',
            'active_days' => fake()->randomElement([90, 180, 365]),
            'is_featured' => false,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function featured(): self
    {
        return $this->state(fn (): array => ['is_featured' => true]);
    }

    /**
     * Attach a full flag set after creation — quota keys take the given numbers,
     * every switch key listed is turned on and the rest stay off.
     *
     * @param  array<string, int|bool|null>  $features  key => number, null for unlimited, or bool
     */
    public function withFeatures(array $features): self
    {
        return $this->afterCreating(function (Package $package) use ($features): void {
            foreach ($features as $key => $value) {
                $featureKey = FeatureKey::from($key);

                $package->features()->create([
                    'feature_key' => $featureKey->value,
                    'feature_value' => match (true) {
                        $value === null => null,
                        $featureKey->isQuota() => (string) (int) $value,
                        default => $value ? 'true' : 'false',
                    },
                    'is_unlimited' => $featureKey->isQuota() && $value === null,
                ]);
            }
        });
    }
}
