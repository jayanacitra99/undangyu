{{--
    "Acara" tab (17.3) — the EventSessionsEditor island.
--}}
<div
    data-island="event-sessions-editor"
    data-events="{{ json_encode($events) }}"
    data-store-url="{{ route('api.events.store', $invitation) }}"
    data-reorder-url="{{ route('api.events.reorder', $invitation) }}"
    data-item-url-template="{{ route('api.events.update', ['event' => '__ID__']) }}"
    data-csrf-token="{{ csrf_token() }}"
    data-timezone-label="{{ App\Models\Invitation::TIMEZONES[$invitation->timezone] ?? $invitation->timezone }}"
    data-can-edit="{{ $canEdit ? '1' : '0' }}"
></div>

@push('scripts')
    @vite('resources/js/islands/event-sessions-editor.js')
@endpush
