<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WishStatus;
use App\Models\Concerns\PartOfInvitation;
use App\Models\Contracts\BelongsToInvitation;
use Database\Factories\WishFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One guestbook message (M6.4, docs/03 § 3.5).
 *
 * The message is stored exactly as it was typed, including any angle brackets
 * in it. Sanitising on the way in loses what the guest wrote and leaves the
 * next reader of the column trusting it; escaping on the way out is the
 * renderer's job and it does it for everything, every time.
 *
 * @property int $invitation_id
 * @property int|null $guest_id
 * @property string $name
 * @property string $message
 * @property WishStatus $status
 * @property bool $is_pinned
 * @property string|null $ip_hash
 */
class Wish extends Model implements BelongsToInvitation
{
    /** @use HasFactory<WishFactory> */
    use HasFactory;

    use PartOfInvitation;

    protected $fillable = [
        'invitation_id',
        'guest_id',
        'name',
        'message',
        'status',
        'is_pinned',
        'ip_hash',
    ];

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
     * The public feed: approved only, pinned first, newest after that.
     *
     * @param  Builder<Wish>  $query
     */
    public function scopePublic(Builder $query): void
    {
        $query->where('status', WishStatus::Approved)
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /**
     * @param  Builder<Wish>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        $query->where(function (Builder $query) use ($like): void {
            $query->where('name', 'like', $like)->orWhere('message', 'like', $like);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => WishStatus::class,
            'is_pinned' => 'boolean',
        ];
    }
}
