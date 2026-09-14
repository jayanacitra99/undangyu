<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TemplateStatus;
use App\Observers\TemplateObserver;
use Database\Factories\TemplateFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * One design in the catalog (docs/03 § 3.3).
 *
 * `view_key` is the folder under resources/js/invitation/templates/ that
 * actually renders it; `config_schema` bounds what a client may customise
 * (docs/05 § 5). Rows are versioned and invitations pin the version, so an
 * edit here never re-renders an invitation that is already published.
 *
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $thumbnail
 * @property string $view_key
 * @property string $version
 * @property int|null $min_package_id
 * @property array<string, mixed> $config_schema
 * @property array<string, mixed> $default_config
 * @property array<string, mixed>|null $demo_data
 * @property bool $is_premium
 * @property string $extra_price
 * @property TemplateStatus $status
 * @property int $usage_count
 * @property int $sort_order
 */
#[ObservedBy(TemplateObserver::class)]
class Template extends Model
{
    /** @use HasFactory<TemplateFactory> */
    use HasFactory;

    use HasSlug;
    use SoftDeletes;

    protected $fillable = [
        'template_category_id',
        'name',
        'slug',
        'description',
        'thumbnail',
        'view_key',
        'version',
        'min_package_id',
        'config_schema',
        'default_config',
        'demo_data',
        'is_premium',
        'extra_price',
        'status',
        'sort_order',
    ];

    /**
     * The slug follows the name until the template first goes live; after that
     * it is frozen, because /templates/{slug} is a shared URL.
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(140)
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<TemplateCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TemplateCategory::class, 'template_category_id');
    }

    /**
     * @return HasMany<TemplateScreenshot, $this>
     */
    public function screenshots(): HasMany
    {
        return $this->hasMany(TemplateScreenshot::class)->orderBy('sort_order');
    }

    /**
     * @return BelongsToMany<EventType, $this>
     */
    public function eventTypes(): BelongsToMany
    {
        return $this->belongsToMany(EventType::class);
    }

    /**
     * The only templates the public gallery may show.
     *
     * @param  Builder<Template>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', TemplateStatus::Published);
    }

    /**
     * @param  Builder<Template>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * @param  Builder<Template>  $query
     */
    public function scopeForEventType(Builder $query, ?string $slug): void
    {
        if ($slug === null || $slug === '') {
            return;
        }

        $query->whereHas('eventTypes', fn (Builder $types) => $types->where('event_types.slug', $slug));
    }

    /**
     * @param  Builder<Template>  $query
     */
    public function scopeForCategory(Builder $query, ?string $slug): void
    {
        if ($slug === null || $slug === '') {
            return;
        }

        $query->whereHas('category', fn (Builder $category) => $category->where('template_categories.slug', $slug));
    }

    /**
     * `premium` or `free`. The real package tier gate arrives with Session 7;
     * until then `is_premium` is the whole tier story.
     *
     * @param  Builder<Template>  $query
     */
    public function scopeForTier(Builder $query, ?string $tier): void
    {
        if (! in_array($tier, ['premium', 'free'], true)) {
            return;
        }

        $query->where('is_premium', $tier === 'premium');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'config_schema' => 'array',
            'default_config' => 'array',
            'demo_data' => 'array',
            'is_premium' => 'boolean',
            'extra_price' => 'decimal:2',
            'status' => TemplateStatus::class,
            'usage_count' => 'integer',
            'sort_order' => 'integer',
        ];
    }
}
