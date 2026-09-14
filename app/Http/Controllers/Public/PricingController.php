<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\FeatureKey;
use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Services\Entitlements\EntitlementResolver;
use App\Support\PricingCache;
use Illuminate\Contracts\View\View;

/**
 * The public pricing page at /harga (M2.3).
 *
 * The comparison table is generated from the resolved entitlements, so adding a
 * capability is a seed and a translation, never an edit here or in the Blade.
 * Read-only, cached, and plain arrays only — see TemplateCache for why.
 */
final class PricingController extends Controller
{
    public function __construct(private readonly EntitlementResolver $resolver) {}

    public function __invoke(): View
    {
        /** @var array{packages: list<array<string, mixed>>, rows: list<array<string, mixed>>} $table */
        $table = PricingCache::remember(PricingCache::KEY, fn (): array => $this->build());

        return view('pricing.index', $table);
    }

    /**
     * @return array{packages: list<array<string, mixed>>, rows: list<array<string, mixed>>}
     */
    private function build(): array
    {
        $packages = Package::query()->active()->ordered()->get();

        $entitlements = $packages
            ->mapWithKeys(fn (Package $package): array => [
                $package->getKey() => $this->resolver->resolve($package),
            ])
            ->all();

        return [
            'packages' => $packages->map(fn (Package $package): array => [
                'name' => $package->name,
                'slug' => $package->slug,
                'description' => $package->description,
                'price' => (float) $package->price,
                'effective_price' => $package->effectivePrice(),
                'has_discount' => $package->discount_price !== null,
                'currency' => $package->currency,
                'active_days' => $package->active_days,
                'is_featured' => $package->is_featured,
            ])->all(),

            'rows' => array_map(fn (FeatureKey $key): array => [
                'label' => $key->label(),
                'is_quota' => $key->isQuota(),
                'values' => $packages
                    ->map(fn (Package $package) => $entitlements[$package->getKey()][$key->value])
                    ->all(),
            ], FeatureKey::cases()),
        ];
    }
}
