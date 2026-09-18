<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\PartOfInvitation;
use App\Models\Contracts\BelongsToInvitation;
use Database\Factories\GuestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * One named invitee (M5.1, docs/03 § 3.5).
 *
 * `token` is the `?to=` value. It is generated from the name plus a random
 * suffix — never sequential, never the id (hard rule 7) — and is unique across
 * the whole table, so a token alone resolves a guest without also being told
 * which invitation it belongs to.
 *
 * Soft-deleted, because a deleted guest may have already RSVP'd and their
 * response should not vanish from the count with them.
 *
 * @property int $invitation_id
 * @property int|null $guest_group_id
 * @property string|null $title
 * @property string $name
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $address
 * @property string $token
 * @property int $max_pax
 * @property bool $is_vip
 * @property string|null $table_number
 * @property string|null $notes
 * @property Carbon|null $sent_at
 * @property Carbon|null $opened_at
 * @property int $open_count
 */
class Guest extends Model implements BelongsToInvitation
{
    /** @use HasFactory<GuestFactory> */
    use HasFactory;

    use PartOfInvitation, SoftDeletes;

    /**
     * Bapak / Ibu / Saudara / Saudari — a closed list, but a string column
     * rather than an enum: the honorifics that fit a wedding do not fit a
     * graduation, and the set will grow per event type.
     *
     * @var list<string>
     */
    public const TITLES = ['Bapak', 'Ibu', 'Bapak & Ibu', 'Saudara', 'Saudari', 'Keluarga'];

    protected $fillable = [
        'invitation_id',
        'guest_group_id',
        'title',
        'name',
        'phone',
        'email',
        'address',
        'token',
        'max_pax',
        'is_vip',
        'table_number',
        'notes',
        'sent_at',
    ];

    /**
     * @return BelongsTo<Invitation, $this>
     */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    /**
     * @return BelongsTo<GuestGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(GuestGroup::class, 'guest_group_id');
    }

    /**
     * How the client addresses this guest on a message or a card.
     */
    public function displayName(): string
    {
        return trim(($this->title ?? '').' '.$this->name);
    }

    /**
     * Free-text search across the columns a client actually searches by. The
     * name index does not serve a leading wildcard, which is fine at the
     * thousand-row scale this table is specified for and honest about what it
     * costs — a client with 1000 guests still pages 25 at a time.
     *
     * @param  Builder<Guest>  $query
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
                ->orWhere('email', 'like', $like)
                ->orWhere('table_number', 'like', $like);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'max_pax' => 'integer',
            'is_vip' => 'boolean',
            'open_count' => 'integer',
            'sent_at' => 'datetime',
            'opened_at' => 'datetime',
        ];
    }
}
