<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FeatureKey;
use App\Enums\InvitationStatus;
use App\Enums\InvitationVisibility;
use App\Facades\Setting;
use App\Models\Concerns\ScopedToUser;
use Database\Factories\InvitationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * One event's invitation site (docs/03 § 3.4).
 *
 * `entitlements` is the resolved package snapshot taken at provisioning. Quota
 * checks read it from here for the life of the invitation — changing what
 * "Premium" includes next year must not change what an already-sold
 * invitation was promised.
 *
 * @property string $uuid
 * @property int $user_id
 * @property int|null $created_by
 * @property int|null $order_id
 * @property int $event_type_id
 * @property int $template_id
 * @property string $template_version
 * @property int $package_id
 * @property string $slug
 * @property string|null $custom_domain
 * @property string $title
 * @property InvitationStatus $status
 * @property InvitationVisibility $visibility
 * @property string|null $password
 * @property string $language
 * @property string $timezone
 * @property array<string, mixed>|null $theme_config
 * @property array<string, mixed> $settings
 * @property array<string, int|bool|null> $entitlements
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $og_image_path
 * @property int $view_count
 * @property Carbon|null $published_at
 * @property Carbon|null $expires_at
 */
class Invitation extends Model
{
    /** @use HasFactory<InvitationFactory> */
    use HasFactory;

    use HasSlug;
    use HasUuids;

    // Defence in depth behind InvitationPolicy: a client's queries see only
    // their own rows, so a controller that forgets to scope a listing leaks
    // nothing. Staff and queue workers run unscoped — see ScopedToUser.
    use ScopedToUser;
    use SoftDeletes;

    /**
     * Slugs that would collide with a route or read as official. The seeded
     * `invitation.slug_blocklist` setting is added on top of these.
     *
     * @var list<string>
     */
    public const RESERVED_SLUGS = [
        'admin', 'api', 'dashboard', 'login', 'logout', 'register', 'checkout',
        'harga', 'templates', 'preview', 'scanner', 'webhooks', 'undangyu',
    ];

    /**
     * The languages an invitation can be written in. Indonesian is the
     * product; English is there for the guests who do not read it.
     *
     * @var array<string, string>
     */
    public const LANGUAGES = [
        'id' => 'Bahasa Indonesia',
        'en' => 'English',
    ];

    /**
     * Indonesia's three zones, which is where the events are. Storage stays
     * UTC — this is only what a guest reads off the invitation.
     *
     * @var array<string, string>
     */
    public const TIMEZONES = [
        'Asia/Jakarta' => 'WIB (Jakarta)',
        'Asia/Makassar' => 'WITA (Makassar)',
        'Asia/Jayapura' => 'WIT (Jayapura)',
    ];

    protected $fillable = [
        'user_id',
        'created_by',
        'order_id',
        'event_type_id',
        'template_id',
        'template_version',
        'package_id',
        'slug',
        'custom_domain',
        'title',
        'status',
        'visibility',
        'password',
        'language',
        'timezone',
        'theme_config',
        'settings',
        'entitlements',
        'meta_title',
        'meta_description',
        'og_image_path',
        'published_at',
        'expires_at',
    ];

    /**
     * Only `uuid` is generated; the primary key stays an auto-incrementing
     * bigint, because every child table points at it.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * The slug is the public URL, so it is frozen once created and never
     * follows a retitle. Reserved words get a suffix rather than a rejection —
     * a client naming their event "Preview" should still get an invitation.
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(120)
            ->doNotGenerateSlugsOnUpdate()
            ->preventOverwrite()
            ->usingSeparator('-');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Reserved words, from the constant above plus the seeded setting so an
     * admin can add one without a deploy.
     *
     * @return list<string>
     */
    public static function blockedSlugs(): array
    {
        /** @var list<string> $configured */
        $configured = Setting::get('invitation.slug_blocklist', []);

        return array_values(array_unique(array_map(
            'strtolower',
            [...self::RESERVED_SLUGS, ...$configured],
        )));
    }

    public static function slugIsBlocked(string $slug): bool
    {
        return in_array(strtolower($slug), self::blockedSlugs(), true);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<EventType, $this>
     */
    public function eventType(): BelongsTo
    {
        return $this->belongsTo(EventType::class);
    }

    /**
     * @return BelongsTo<Template, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /**
     * @return BelongsTo<Package, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * @return HasMany<InvitationSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(InvitationSection::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<InvitationPerson, $this>
     */
    public function persons(): HasMany
    {
        return $this->hasMany(InvitationPerson::class)->orderBy('sort_order');
    }

    /**
     * `chaperone()` hydrates the inverse relation on each child, so an event
     * loaded this way can read the invitation's timezone without a lazy load —
     * which is what its local_start_at accessor needs.
     *
     * @return HasMany<InvitationEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(InvitationEvent::class)->orderBy('sort_order')->chaperone();
    }

    /**
     * The earliest dated occasion — what the invitation list shows as "when",
     * as a relation so a listing can eager load it instead of loading every
     * event of every invitation.
     *
     * @return HasOne<InvitationEvent, $this>
     */
    public function firstEvent(): HasOne
    {
        // chaperone(), for the same reason events() has it: local_start_at
        // reads the invitation's timezone off the inverse relation, and
        // without this it silently falls back to the app's.
        return $this->hasOne(InvitationEvent::class)->ofMany('start_at', 'min')->chaperone();
    }

    /**
     * @return HasMany<InvitationStory, $this>
     */
    public function stories(): HasMany
    {
        return $this->hasMany(InvitationStory::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<InvitationMedia, $this>
     */
    public function media(): HasMany
    {
        return $this->hasMany(InvitationMedia::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<InvitationGift, $this>
     */
    public function gifts(): HasMany
    {
        return $this->hasMany(InvitationGift::class)->orderBy('sort_order');
    }

    /**
     * What this invitation was sold, not what the package grants today.
     */
    public function entitlement(FeatureKey $key): int|bool|null
    {
        return array_key_exists($key->value, $this->entitlements)
            ? $this->entitlements[$key->value]
            : $key->default();
    }

    public function isLive(): bool
    {
        return $this->status->isPubliclyVisible()
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * @param  Builder<Invitation>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', InvitationStatus::Published);
    }

    /**
     * Published, but past its window — what the expiry sweep looks for.
     *
     * @param  Builder<Invitation>  $query
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->where('status', InvitationStatus::Published)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InvitationStatus::class,
            'visibility' => InvitationVisibility::class,
            'password' => 'hashed',
            'theme_config' => 'array',
            'settings' => 'array',
            'entitlements' => 'array',
            'view_count' => 'integer',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
