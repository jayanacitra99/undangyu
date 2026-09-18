<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MessageChannel;
use Database\Factories\MessageTemplateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A reusable message text (M8.2, docs/03 § 3.6).
 *
 * Three scopes share the table. A system template belongs to nobody and is
 * what a client starts from; an invitation template is this wedding's own
 * wording; a user template sits between them for a reseller who says the same
 * thing to every client's guests.
 *
 * Not an invitation child: a system template has no invitation, so the child
 * policy — which resolves the parent and asks about it — has nothing to ask.
 * MessageTemplatePolicy decides per scope instead.
 *
 * @property int|null $user_id
 * @property int|null $invitation_id
 * @property string $name
 * @property MessageChannel $channel
 * @property string|null $subject
 * @property string $body
 * @property list<string>|null $variables
 * @property bool $is_system
 */
class MessageTemplate extends Model
{
    /** @use HasFactory<MessageTemplateFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'invitation_id',
        'name',
        'channel',
        'subject',
        'body',
        'variables',
        'is_system',
    ];

    /**
     * @return BelongsTo<Invitation, $this>
     */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * What one invitation may send with: its own templates, its owner's, and
     * the system defaults — in that order, because a client's own wording
     * should be the first thing the picker offers.
     *
     * @param  Builder<MessageTemplate>  $query
     */
    public function scopeAvailableTo(Builder $query, Invitation $invitation): void
    {
        $query->where(function (Builder $query) use ($invitation): void {
            $query->where('invitation_id', $invitation->getKey())
                ->orWhere(function (Builder $query) use ($invitation): void {
                    $query->whereNull('invitation_id')
                        ->where('user_id', $invitation->user_id);
                })
                ->orWhere(function (Builder $query): void {
                    $query->whereNull('invitation_id')
                        ->whereNull('user_id')
                        ->where('is_system', true);
                });
        })->orderByRaw('invitation_id IS NULL, user_id IS NULL, id');
    }

    /**
     * A seeded default is read-only to clients: they copy it and edit the
     * copy, so that an edit never removes the wording every other invitation
     * starts from.
     */
    public function isEditable(): bool
    {
        return ! $this->is_system;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => MessageChannel::class,
            'variables' => 'array',
            'is_system' => 'boolean',
        ];
    }
}
