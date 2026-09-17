<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\PartOfInvitation;
use App\Models\Contracts\BelongsToInvitation;
use Database\Factories\InvitationStoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One moment on the "our story" timeline (docs/03 § 3.4).
 *
 * `date` is a plain date, not a datetime: "Juli 2019" is a memory, not an
 * appointment, so there is no timezone to render it in.
 *
 * @property int $invitation_id
 * @property Carbon|null $date
 * @property string $title
 * @property string|null $description
 * @property string|null $image
 * @property int $sort_order
 */
class InvitationStory extends Model implements BelongsToInvitation
{
    /** @use HasFactory<InvitationStoryFactory> */
    use HasFactory;

    use PartOfInvitation;

    protected $fillable = [
        'invitation_id',
        'date',
        'title',
        'description',
        'image',
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
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'sort_order' => 'integer',
        ];
    }
}
