<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\PackageObserver;
use Database\Factories\PackageFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * What a client buys (docs/03 § 3.2).
 *
 * Limits are never read off a live package once an invitation exists — they are
 * resolved here, snapshotted onto `invitations.entitlements` at provisioning,
 * and read from that snapshot afterwards.
 *
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $price
 * @property string|null $discount_price
 * @property string $currency
 * @property int $active_days
 * @property bool $is_featured
 * @property bool $is_active
 * @property int $sort_order
 */
#[ObservedBy(PackageObserver::class)]
class Package extends Model
{
    /** @use HasFactory<PackageFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'discount_price',
        'currency',
        'active_days',
        'is_featured',
        'is_active',
        'sort_order',
    ];

    /**
     * @return HasMany<PackageFeature, $this>
     */
    public function features(): HasMany
    {
        return $this->hasMany(PackageFeature::class);
    }

    /**
     * What the client actually pays, as the decimal string the column holds.
     *
     * Every downstream total is computed from this, so it must not become a
     * float on the way out — see App\Support\Money.
     */
    public function effectivePrice(): string
    {
        return (string) ($this->discount_price ?? $this->price);
    }

    /**
     * @param  Builder<Package>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<Package>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('price');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'active_days' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
