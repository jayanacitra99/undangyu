/*
| Add-to-calendar (22.5).
|
| Two routes, because phones differ: a Google Calendar URL for anyone signed
| into Google, and a downloadable `.ics` for iOS, Outlook and everything else.
| Both are built client-side — no round trip, and nothing to keep in sync
| server-side.
|
| The times in the payload are wall-clock in the invitation's timezone, with
| its offset attached (`2026-12-12T19:00:00+07:00`). Calendars want UTC in
| basic format, so the conversion here is the one place that reads that offset
| — and it reads it from the string rather than assuming the guest's own zone,
| which is the whole point.
*/

/**
 * `20261212T120000Z` — what both formats expect.
 */
function toUtcBasic(value) {
    return new Date(value).toISOString().replace(/[-:]/g, '').replace(/\.\d{3}/, '');
}

/**
 * An event with no end time gets two hours, which is the shape of an
 * Indonesian reception and better than a zero-length calendar entry.
 */
function endOf(event) {
    if (event.end_at) {
        return event.end_at;
    }

    const start = new Date(event.start_at);
    start.setHours(start.getHours() + 2);

    return start.toISOString();
}

function describe(event, invitation) {
    return [invitation.title, event.description, event.dress_code ? `Dress code: ${event.dress_code}` : null]
        .filter(Boolean)
        .join(' — ');
}

export function googleCalendarUrl(event, invitation) {
    const params = new URLSearchParams({
        action: 'TEMPLATE',
        text: `${event.name} · ${invitation.title}`,
        dates: `${toUtcBasic(event.start_at)}/${toUtcBasic(endOf(event))}`,
        details: describe(event, invitation),
        location: [event.venue_name, event.address].filter(Boolean).join(', '),
        ctz: invitation.timezone,
    });

    return `https://calendar.google.com/calendar/render?${params.toString()}`;
}

export function icsFor(event, invitation) {
    /*
    | Folded at nothing and escaped minimally on purpose: commas, semicolons
    | and newlines are the three characters that break an ICS line, and a venue
    | address contains all three.
    */
    const escape = (value) =>
        String(value ?? '')
            .replace(/\\/g, '\\\\')
            .replace(/;/g, '\\;')
            .replace(/,/g, '\\,')
            .replace(/\r?\n/g, '\\n');

    return [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//Undangyu//Invitation//ID',
        'CALSCALE:GREGORIAN',
        'METHOD:PUBLISH',
        'BEGIN:VEVENT',
        `UID:${invitation.uuid}-${escape(event.name)}`,
        `DTSTAMP:${toUtcBasic(new Date().toISOString())}`,
        `DTSTART:${toUtcBasic(event.start_at)}`,
        `DTEND:${toUtcBasic(endOf(event))}`,
        `SUMMARY:${escape(`${event.name} · ${invitation.title}`)}`,
        `DESCRIPTION:${escape(describe(event, invitation))}`,
        `LOCATION:${escape([event.venue_name, event.address].filter(Boolean).join(', '))}`,
        'END:VEVENT',
        'END:VCALENDAR',
    ].join('\r\n');
}

export function downloadIcs(event, invitation) {
    const blob = new Blob([icsFor(event, invitation)], { type: 'text/calendar;charset=utf-8' });
    const url = URL.createObjectURL(blob);

    const link = document.createElement('a');
    link.href = url;
    link.download = `${invitation.slug}-${event.name.toLowerCase().replace(/\s+/g, '-')}.ics`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    URL.revokeObjectURL(url);
}

/**
 * "Open in Maps" (22.5).
 *
 * Coordinates when the client gave or a pasted link yielded them — that opens
 * the right pin rather than a search for a venue name that three buildings
 * share. Otherwise the client's own link, and only then a name search.
 */
export function mapsUrl(event) {
    if (event.latitude && event.longitude) {
        return `https://www.google.com/maps/search/?api=1&query=${event.latitude},${event.longitude}`;
    }

    if (event.maps_url) {
        return event.maps_url;
    }

    const query = [event.venue_name, event.address].filter(Boolean).join(', ');

    return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(query)}`;
}
