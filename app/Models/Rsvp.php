<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RsvpAttendance;
use App\Models\Concerns\PartOfInvitation;
use App\Models\Contracts\BelongsToInvitation;
use Database\Factories\RsvpFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * One attendance answer (M6.1, M6.9, docs/03 § 3.5).
 *
 * No cache observer. The payload a guest is served carries no RSVPs, and if it
 * did, five hundred answers would flush it five hundred times on the busiest
 * day of the invitation's life.
 *
 * @property int $invitation_id
 * @property int|null $guest_id
 * @property int|null $invitation_event_id
 * @property string $name
 * @property string|null $phone
 * @property RsvpAttendance $attendance
 * @property int $pax
 * @property string|null $meal_preference
 * @property string|null $notes
 * @property string|null $ip_hash
 * @property string|null $user_agent
 * @property Carbon $responded_at
 */
class Rsvp extends Model implements BelongsToInvitation
{
    /** @use HasFactory<RsvpFactory> */
    use HasFactory;

    use PartOfInvitation;

    protected $fillable = [
        'invitation_id',
        'guest_id',
        'invitation_event_id',
        'name',
        'phone',
        'attendance',
        'pax',
        'meal_preference',
        'notes',
        'ip_hash',
        'user_agent',
        'responded_at',
    ];

    /**
     * A one-way hash of the address, salted with the application key so the
     * same guest is recognisable within this installation and nowhere else.
     * IPv4 is a 32-bit space — an unsalted hash of one is a lookup table away
     * from the address itself.
     */
    public static function hashIp(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        return hash_hmac('sha256', $ip, (string) config('app.key'));
    }

    /**
     * @return BelongsTo<Invitation, $this>
     */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    /**
     * @return BelongsTo<Guest, $this>
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    /**
     * @return BelongsTo<InvitationEvent, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(InvitationEvent::class, 'invitation_event_id');
    }

    /**
     * @param  Builder<Rsvp>  $query
     */
    public function scopeAttending(Builder $query): void
    {
        $query->where('attendance', RsvpAttendance::Yes);
    }

    /**
     * Free-text search over what a client actually looks someone up by.
     *
     * @param  Builder<Rsvp>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        $query->where(function (Builder $query) use ($like): void {
            $query->where('name', 'like', $like)
                ->orWhere('phone', 'like', $like)
                ->orWhere('notes', 'like', $like);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attendance' => RsvpAttendance::class,
            'pax' => 'integer',
            'responded_at' => 'datetime',
        ];
    }
}
