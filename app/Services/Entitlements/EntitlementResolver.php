<?php

declare(strict_types=1);

namespace App\Services\Entitlements;

use App\Enums\FeatureKey;
use App\Models\Package;
use Illuminate\Support\Facades\Cache;

/**
 * Turns a package's feature rows into the flat map the rest of the system uses
 * (playbook 7.3).
 *
 * The output is snapshotted onto `invitations.entitlements` at provisioning and
 * read from there for the life of the invitation, so it has to be stable and
 * serialisable: plain scalars keyed by the FeatureKey string, every key always
 * present, `null` meaning unlimited.
 */
final class EntitlementResolver
{
    public const CACHE_PREFIX = 'entitlements:package:';

    public const TTL = 86400;

    /**
     * @return array<string, int|bool|null>
     */
    public function resolve(Package $package): array
    {
        return Cache::remember(
            self::CACHE_PREFIX.$package->getKey(),
            self::TTL,
            fn (): array => $this->build($package),
        );
    }

    /**
     * Is there room for one more, or is the switch on?
     *
     * `$current` is what the client has already used. A quota at its limit is
     * full — 500 guests on a 500-guest package means the next guest is refused,
     * not allowed.
     *
     * @param  array<string, int|bool|null>  $entitlements
     */
    public function check(array $entitlements, FeatureKey $key, int $current = 0): bool
    {
        // `??` would read an unlimited quota — stored as null — as a missing
        // key and fall back to the default of 0, refusing everything.
        $value = array_key_exists($key->value, $entitlements)
            ? $entitlements[$key->value]
            : $key->default();

        // Unlimited. Only quota keys are ever stored as null.
        if ($value === null) {
            return true;
        }

        if (! $key->isQuota()) {
            return (bool) $value;
        }

        return $current < (int) $value;
    }

    public function forget(Package $package): void
    {
        Cache::forget(self::CACHE_PREFIX.$package->getKey());
    }

    /**
     * @return array<string, int|bool|null>
     */
    private function build(Package $package): array
    {
        $rows = $package->features()->get()->keyBy(fn ($feature): string => $feature->feature_key->value);

        $entitlements = [];

        foreach (FeatureKey::cases() as $key) {
            $feature = $rows->get($key->value);

            if ($feature === null) {
                $entitlements[$key->value] = $key->default();

                continue;
            }

            if ($key->isQuota()) {
                $entitlements[$key->value] = $feature->is_unlimited
                    ? null
                    : (int) $feature->feature_value;

                continue;
            }

            // A switch is never unlimited, but an admin can tick the box; read
            // it as on rather than throwing the row away.
            $entitlements[$key->value] = $feature->is_unlimited
                || filter_var($feature->feature_value, FILTER_VALIDATE_BOOL);
        }

        return $entitlements;
    }
}
