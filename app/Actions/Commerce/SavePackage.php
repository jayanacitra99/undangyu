<?php

declare(strict_types=1);

namespace App\Actions\Commerce;

use App\Models\Package;
use App\Services\Entitlements\EntitlementResolver;
use App\Support\PricingCache;
use Illuminate\Support\Facades\DB;

/**
 * Creates or updates one package together with its feature flags (M2.1, M2.2).
 *
 * The flag set is replaced wholesale rather than diffed: the editor posts the
 * complete set every time, and a row the admin removed has to disappear.
 */
final class SavePackage
{
    public function __construct(private readonly EntitlementResolver $resolver) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<array{feature_key: string, feature_value: ?string, is_unlimited: bool}>  $features
     */
    public function __invoke(?Package $package, array $attributes, array $features): Package
    {
        $saved = DB::transaction(function () use ($package, $attributes, $features): Package {
            if ($package === null) {
                $package = Package::query()->create($attributes);
            } else {
                $package->update($attributes);
            }

            // A mass delete fires no model events, and an update that changed
            // nothing but the flags fires none either — so the cache is flushed
            // below rather than left to the observers.
            $package->features()->delete();

            foreach ($features as $feature) {
                $package->features()->create($feature);
            }

            return $package->refresh();
        });

        $this->resolver->forget($saved);
        PricingCache::flush();

        return $saved;
    }
}
