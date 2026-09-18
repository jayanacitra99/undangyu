<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Models\Guest;
use App\Models\Invitation;
use Illuminate\Validation\Rule;

/**
 * The guest field rules, shared by the create and update requests (M5.1).
 *
 * Both requests validate the same columns against the same limits; what
 * differs is the quota check and where the invitation comes from. Keeping the
 * fields in one place is what stops an `address` cap from being raised on
 * create and forgotten on update.
 */
final class GuestRules
{
    /**
     * @var array<string, string>
     */
    public const ATTRIBUTES = [
        'title' => 'sebutan',
        'name' => 'nama',
        'phone' => 'nomor WhatsApp',
        'email' => 'email',
        'address' => 'alamat',
        'guest_group_id' => 'grup',
        'max_pax' => 'jumlah orang',
        'is_vip' => 'tamu VIP',
        'table_number' => 'nomor meja',
        'notes' => 'catatan',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function fields(Invitation $invitation, bool $required = true): array
    {
        return [
            'title' => ['nullable', Rule::in(Guest::TITLES)],
            'name' => [$required ? 'required' : 'sometimes', 'string', 'max:190'],

            // Loose on shape, strict on length: the action normalises to
            // E.164, and a client who types "0812 3456 7890" has given us a
            // perfectly good number.
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email:rfc', 'max:190'],
            'address' => ['nullable', 'string', 'max:500'],

            // The group must belong to this invitation. Without the where,
            // a client could file their guests under another client's group
            // and read its name back off every row.
            'guest_group_id' => [
                'nullable',
                Rule::exists('guest_groups', 'id')->where('invitation_id', $invitation->getKey()),
            ],

            // tinyint, and nobody brings 255 people on one invitation.
            'max_pax' => ['nullable', 'integer', 'min:1', 'max:20'],
            'is_vip' => ['nullable', 'boolean'],
            'table_number' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
