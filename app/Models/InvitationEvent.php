<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\PartOfInvitation;
use App\Models\Contracts\BelongsToInvitation;
use App\Observers\InvalidatesInvitationCache;
use Database\Factories\InvitationEventFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One dated occasion within an invitation (docs/03 § 3.4).
 *
 * `start_at` and `end_at` are stored UTC. The accessors below render them in
 * the invitation's own timezone, which is the only time a guest should ever
 * see — a Jakarta wedding reads 19:00 whether the viewer is in Bali or Berlin.
 *
 * @property int $invitation_id
 * @property string $name
 * @property string|null $description
 * @property Carbon $start_at
 * @property Carbon|null $end_at
 * @property bool $is_all_day
 * @property string $venue_name
 * @property string|null $address
 * @property string|null $maps_url
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string|null $dress_code
 * @property string|null $live_stream_url
 * @property string|null $notes
 * @property int $sort_order
 * @property-read Carbon $local_start_at
 * @property-read Carbon|null $local_end_at
 */
#[ObservedBy(InvalidatesInvitationCache::class)]
class InvitationEvent extends Model implements BelongsToInvitation
{
    /** @use HasFactory<InvitationEventFactory> */
    use HasFactory;

    use PartOfInvitation;

    protected $fillable = [
        'invitation_id',
        'name',
        'description',
        'start_at',
        'end_at',
        'is_all_day',
        'venue_name',
        'address',
        'maps_url',
        'latitude',
        'longitude',
        'dress_code',
        'live_stream_url',
        'notes',
        'sort_order',
    ];

    /**
     * @return BelongsTo<Invitation, $this>
     */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    /**
     * @return Attribute<Carbon, never>
     */
    protected function localStartAt(): Attribute
    {
        return Attribute::get(fn (): Carbon => $this->start_at->timezone($this->displayTimezone()));
    }

    /**
     * @return Attribute<Carbon|null, never>
     */
    protected function localEndAt(): Attribute
    {
        return Attribute::get(fn (): ?Carbon => $this->end_at?->timezone($this->displayTimezone()));
    }

    /**
     * Does this event have a map to point at?
     */
    public function hasLocation(): bool
    {
        return $this->maps_url !== null
            || ($this->latitude !== null && $this->longitude !== null);
    }

    /**
     * The invitation's timezone, falling back to the app's when the relation is
     * not loaded and cannot be — a payload built without it should still render.
     */
    private function displayTimezone(): string
    {
        return $this->relationLoaded('invitation')
            ? $this->invitation->timezone
            : (string) config('app.timezone');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'is_all_day' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'sort_order' => 'integer',
        ];
    }
}
