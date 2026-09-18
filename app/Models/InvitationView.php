<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\PartOfInvitation;
use App\Models\Contracts\BelongsToInvitation;
use Database\Factories\InvitationViewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One raw view (M9.1, docs/03 § 3.7).
 *
 * Nothing user facing reads this model. It exists so the rollup has something
 * to aggregate and the prune has something to delete; the client's charts read
 * InvitationStatsDaily and only that.
 *
 * No timestamps: `viewed_at` is the only time that means anything here, and a
 * created_at/updated_at pair on tens of thousands of rows a weekend is two
 * more columns to write and store for nothing.
 *
 * @property int $invitation_id
 * @property int|null $guest_id
 * @property string|null $ip_hash
 * @property string|null $device_type
 * @property Carbon $viewed_at
 */
class InvitationView extends Model implements BelongsToInvitation
{
    /** @use HasFactory<InvitationViewFactory> */
    use HasFactory;

    use PartOfInvitation;

    public $timestamps = false;

    protected $fillable = [
        'invitation_id',
        'guest_id',
        'ip_hash',
        'user_agent',
        'referrer',
        'device_type',
        'country',
        'viewed_at',
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
        return ['viewed_at' => 'datetime'];
    }
}
