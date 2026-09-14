<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Package;
use App\Services\Entitlements\EntitlementResolver;
use App\Support\PricingCache;

/**
 * A package edit invalidates both the resolved entitlements and the public
 * pricing table (CLAUDE.md hard rule 5).
 */
class PackageObserver
{
    public function __construct(private readonly EntitlementResolver $resolver) {}

    public function saved(Package $package): void
    {
        $this->flush($package);
    }

    public function deleted(Package $package): void
    {
        $this->flush($package);
    }

    private function flush(Package $package): void
    {
        $this->resolver->forget($package);
        PricingCache::flush();
    }
}
