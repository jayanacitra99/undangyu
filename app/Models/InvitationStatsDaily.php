<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\PartOfInvitation;
use App\Models\Contracts\BelongsToInvitation;
use Database\Factories\InvitationStatsDailyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One invitation's numbers for one day (M9.2, docs/03 § 3.7).
 *
 * This is the table the client's analytics tab queries. The raw views table is
 * for the rollup and the prune, and a chart that reaches into it is a chart
 * that gets slower every weekend until it times out on the busiest invitation
 * we have.
 *
 * @property int $invitation_id
 * @property Carbon $date
 * @property int $views
 * @property int $unique_visitors
 * @property int $rsvp_yes
 * @property int $rsvp_no
 * @property int $rsvp_maybe
 * @property int $total_pax
 * @property int $wishes_count
 * @property int $guest_opens
 */
class InvitationStatsDaily extends Model implements BelongsToInvitation
{
    /** @use HasFactory<InvitationStatsDailyFactory> */
    use HasFactory;

    use PartOfInvitation;

    protected $table = 'invitation_stats_daily';

    protected $fillable = [
        'invitation_id',
        'date',
        'views',
        'unique_visitors',
        'rsvp_yes',
        'rsvp_no',
        'rsvp_maybe',
        'total_pax',
        'wishes_count',
        'guest_opens',
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
            'views' => 'integer',
            'unique_visitors' => 'integer',
            'rsvp_yes' => 'integer',
            'rsvp_no' => 'integer',
            'rsvp_maybe' => 'integer',
            'total_pax' => 'integer',
            'wishes_count' => 'integer',
            'guest_opens' => 'integer',
        ];
    }
}
