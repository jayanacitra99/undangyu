<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FeatureKey;
use App\Observers\PackageFeatureObserver;
use Database\Factories\PackageFeatureFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One capability of one package (docs/03 § 3.2).
 *
 * `feature_value` is a string on purpose: the column holds "500" and "true"
 * alike. FeatureKey decides which it is, and EntitlementResolver does the
 * reading — nothing consumes this raw.
 *
 * @property int $package_id
 * @property FeatureKey $feature_key
 * @property string|null $feature_value
 * @property bool $is_unlimited
 */
#[ObservedBy(PackageFeatureObserver::class)]
class PackageFeature extends Model
{
    /** @use HasFactory<PackageFeatureFactory> */
    use HasFactory;

    protected $fillable = [
        'package_id',
        'feature_key',
        'feature_value',
        'is_unlimited',
    ];

    /**
     * @return BelongsTo<Package, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'feature_key' => FeatureKey::class,
            'is_unlimited' => 'boolean',
        ];
    }
}
