<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GiftType;
use App\Models\Concerns\PartOfInvitation;
use App\Models\Contracts\BelongsToInvitation;
use Database\Factories\InvitationGiftFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Where a guest sends a gift (docs/03 § 3.4).
 *
 * `account_number` uses the `encrypted` cast (hard rule 8). It is shown
 * publicly on the invitation by design — the point is not secrecy from guests,
 * it is that a leaked database dump does not hand over every client's bank
 * details in one query.
 *
 * @property int $invitation_id
 * @property GiftType $type
 * @property string|null $provider_name
 * @property string|null $account_name
 * @property string|null $account_number
 * @property string|null $qris_image
 * @property string|null $recipient_name
 * @property string|null $address
 * @property string|null $notes
 * @property int $sort_order
 */
class InvitationGift extends Model implements BelongsToInvitation
{
    /** @use HasFactory<InvitationGiftFactory> */
    use HasFactory;

    use PartOfInvitation;

    protected $fillable = [
        'invitation_id',
        'type',
        'provider_name',
        'account_name',
        'account_number',
        'qris_image',
        'recipient_name',
        'address',
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
     * The last four digits, for an admin screen that should not print the
     * whole number back out.
     */
    public function maskedAccountNumber(): ?string
    {
        if ($this->account_number === null) {
            return null;
        }

        return str_repeat('•', max(0, mb_strlen($this->account_number) - 4))
            .mb_substr($this->account_number, -4);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => GiftType::class,
            'account_number' => 'encrypted',
            'sort_order' => 'integer',
        ];
    }
}
