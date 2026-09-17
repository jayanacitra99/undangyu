<?php

declare(strict_types=1);

namespace App\Actions\Invitations;

use App\Enums\GiftType;
use App\Models\Invitation;
use App\Models\InvitationGift;
use Illuminate\Support\Facades\DB;

/**
 * Creates or updates one gift destination (M4.7).
 *
 * Switching a gift's type clears the fields the new type has no use for: a
 * bank account that becomes a postal address must not keep a stale account
 * number in an encrypted column nobody will ever read again.
 */
final class SaveInvitationGift
{
    /**
     * Which fields each type actually carries.
     *
     * @var array<string, list<string>>
     */
    public const FIELDS_BY_TYPE = [
        'bank' => ['provider_name', 'account_name', 'account_number', 'notes'],
        'ewallet' => ['provider_name', 'account_name', 'account_number', 'qris_image', 'notes'],
        'qris' => ['provider_name', 'account_name', 'qris_image', 'notes'],
        'address' => ['recipient_name', 'address', 'notes'],
    ];

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(Invitation $invitation, array $attributes, ?InvitationGift $gift = null): InvitationGift
    {
        return DB::transaction(function () use ($invitation, $attributes, $gift): InvitationGift {
            if ($gift === null) {
                $attributes['sort_order'] = ((int) ($invitation->gifts()->max('sort_order') ?? -1)) + 1;

                return $invitation->gifts()->create($attributes);
            }

            $gift->fill($attributes);

            if ($gift->isDirty('type')) {
                $gift->fill($this->clearedFields($gift->type));
            }

            $gift->save();

            return $gift->refresh();
        });
    }

    /**
     * @return array<string, null>
     */
    private function clearedFields(GiftType $type): array
    {
        $kept = self::FIELDS_BY_TYPE[$type->value];

        $clearable = ['provider_name', 'account_name', 'account_number', 'qris_image', 'recipient_name', 'address'];

        return array_fill_keys(array_diff($clearable, $kept), null);
    }
}
