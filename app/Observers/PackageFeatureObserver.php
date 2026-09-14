<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\PackageFeature;
use App\Services\Entitlements\EntitlementResolver;
use App\Support\PricingCache;

/**
 * A flag is what the resolver reads, so editing one has to invalidate the
 * package's cached entitlements — saving the package itself is not involved.
 */
class PackageFeatureObserver
{
    public function __construct(private readonly EntitlementResolver $resolver) {}

    public function saved(PackageFeature $feature): void
    {
        $this->flush($feature);
    }

    public function deleted(PackageFeature $feature): void
    {
        $this->flush($feature);
    }

    private function flush(PackageFeature $feature): void
    {
        $package = $feature->package()->first();

        if ($package !== null) {
            $this->resolver->forget($package);
        }

        PricingCache::flush();
    }
}
