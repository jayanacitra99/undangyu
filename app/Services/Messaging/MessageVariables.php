<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use App\Models\Guest;
use App\Models\Invitation;
use App\Models\InvitationPerson;
use App\Support\PhoneNumber;

/**
 * Resolves `{guest_name}` and friends into a real message (M8.2, 26.2).
 *
 * One resolver, server-side, used by the live preview, the per-guest link and
 * the bulk workflow alike. The obvious alternative — resolving in JavaScript
 * because the editor previews as you type — means two implementations of the
 * same substitution, and the day they disagree is the day 400 guests get the
 * wrong date.
 *
 * An unknown placeholder is left exactly as typed. A client who writes
 * `{nama}` should see `{nama}` in the preview and go and fix it, not find an
 * empty gap where they cannot tell what happened.
 */
final class MessageVariables
{
    /**
     * Every variable, with the label the picker shows. The order is the order
     * the picker lists them: the two a client actually uses first.
     *
     * @var array<string, string>
     */
    public const VARIABLES = [
        'guest_name' => 'Nama tamu (dengan sebutan)',
        'invitation_url' => 'Tautan undangan tamu ini',
        'couple_names' => 'Nama mempelai',
        'event_date' => 'Tanggal acara',
        'event_time' => 'Jam acara',
        'venue' => 'Nama tempat',
        'invitation_title' => 'Judul undangan',
    ];

    /**
     * Resolve a body against one guest.
     *
     * `$guest` may be null: the editor previews a template before an
     * invitation has any guests, and a preview that refuses to render until
     * the list is imported is a preview nobody sees.
     */
    public function resolve(string $body, Invitation $invitation, ?Guest $guest = null): string
    {
        $values = $this->values($invitation, $guest);

        return preg_replace_callback(
            '/\{([a-z_]+)\}/',
            fn (array $matches): string => $values[$matches[1]] ?? $matches[0],
            $body,
        ) ?? $body;
    }

    /**
     * What each variable stands for, for this invitation and this guest.
     *
     * @return array<string, string>
     */
    public function values(Invitation $invitation, ?Guest $guest = null): array
    {
        // loadMissing rather than a bare read: this is called per guest across
        // a 400-row selection, and preventLazyLoading would throw on the first
        // one if the caller had not loaded them.
        $invitation->loadMissing(['persons', 'firstEvent']);

        $event = $invitation->firstEvent;

        return [
            'guest_name' => $guest?->displayName() ?? 'Bapak/Ibu/Saudara/i',
            'invitation_url' => $this->url($invitation, $guest),
            'couple_names' => $this->coupleNames($invitation),
            'event_date' => $event?->local_start_at->translatedFormat('l, d F Y') ?? '',
            'event_time' => $event?->local_start_at->translatedFormat('H:i') ?? '',
            'venue' => $event === null ? '' : $event->venue_name,
            'invitation_title' => $invitation->title,
        ];
    }

    /**
     * The guest's own link (M5.4). Without a guest — a preview, or a link a
     * client shares publicly — it is the plain invitation URL.
     */
    public function url(Invitation $invitation, ?Guest $guest = null): string
    {
        $url = route('invitation.show', ['slug' => $invitation->slug]);

        return $guest === null ? $url : $url.'?to='.$guest->token;
    }

    /**
     * The `wa.me` deep link (M5.6): digits with no plus, and the message
     * percent-encoded into the query.
     *
     * Null when the guest has no usable number — a client cannot open a chat
     * with someone whose phone they never filled in, and pretending otherwise
     * puts them on wa.me's error page.
     */
    public function whatsappLink(string $message, ?Guest $guest = null): ?string
    {
        $phone = PhoneNumber::forWhatsApp($guest?->phone);

        if ($phone === null) {
            return null;
        }

        // rawurlencode, not urlencode: a space has to be %20, and urlencode's
        // "+" is read as a literal plus by WhatsApp.
        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }

    /**
     * Who the invitation is about, as a client would write it: nicknames
     * joined with "&". Two people for a wedding, but it is whatever the event
     * type's roles gave, so a graduation reads as one name.
     */
    private function coupleNames(Invitation $invitation): string
    {
        $names = $invitation->persons
            ->map(fn (InvitationPerson $person): string => $person->displayName())
            ->filter()
            ->all();

        return implode(' & ', $names);
    }
}
