<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\PartOfInvitation;
use App\Models\Contracts\BelongsToInvitation;
use Database\Factories\GuestGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A named bucket of guests (M5.2, docs/03 § 3.5).
 *
 * No cache observer, unlike the invitation's other children: the public
 * payload carries no guest list, so a group rename has nothing to invalidate.
 *
 * @property int $invitation_id
 * @property string $name
 * @property string|null $color
 * @property int $sort_order
 */
class GuestGroup extends Model implements BelongsToInvitation
{
    /** @use HasFactory<GuestGroupFactory> */
    use HasFactory;

    use PartOfInvitation;

    protected $fillable = [
        'invitation_id',
        'name',
        'color',
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
     * @return HasMany<Guest, $this>
     */
    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}
