<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PersonRole;
use App\Models\Concerns\PartOfInvitation;
use App\Models\Contracts\BelongsToInvitation;
use Database\Factories\InvitationPersonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Someone the event is about (docs/03 § 3.4).
 *
 * `role` is cast to PersonRole, but which roles are allowed depends on the
 * invitation's event type — the FormRequest that writes this checks the row
 * against `event_types.person_roles`, because the schema cannot.
 *
 * @property int $invitation_id
 * @property PersonRole $role
 * @property string $full_name
 * @property string|null $nickname
 * @property string|null $photo
 * @property string|null $bio
 * @property string|null $parent_father
 * @property string|null $parent_mother
 * @property string|null $child_order
 * @property string|null $instagram
 * @property int $sort_order
 */
class InvitationPerson extends Model implements BelongsToInvitation
{
    /** @use HasFactory<InvitationPersonFactory> */
    use HasFactory;

    use PartOfInvitation;

    /**
     * Laravel's pluraliser says `invitation_people`; docs/03 § 3.4 says
     * `invitation_persons`, and the doc is authoritative.
     */
    protected $table = 'invitation_persons';

    protected $fillable = [
        'invitation_id',
        'role',
        'full_name',
        'nickname',
        'photo',
        'bio',
        'parent_father',
        'parent_mother',
        'child_order',
        'instagram',
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
     * What the invitation calls them: the short name if they gave one.
     */
    public function displayName(): string
    {
        return $this->nickname ?: $this->full_name;
    }

    /**
     * Is this role one the invitation's event type actually offers?
     */
    public function roleIsAllowed(): bool
    {
        /** @var list<string> $allowed */
        $allowed = $this->invitation->eventType->person_roles;

        return in_array($this->role->value, $allowed, true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => PersonRole::class,
            'sort_order' => 'integer',
        ];
    }
}
