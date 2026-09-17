{{--
    "Mempelai" tab (17.1) — the PersonsEditor island.

    The roles offered are the event type's own, passed in rather than looked up
    client-side: the FormRequest validates against the same list, and there is
    only one place that decides it.
--}}
<div
    data-island="persons-editor"
    data-persons="{{ json_encode($persons) }}"
    data-roles="{{ json_encode($personRoles) }}"
    data-store-url="{{ route('api.persons.store', $invitation) }}"
    data-reorder-url="{{ route('api.persons.reorder', $invitation) }}"
    data-item-url-template="{{ route('api.persons.update', ['person' => '__ID__']) }}"
    data-photo-url-template="{{ route('api.persons.photo', ['person' => '__ID__']) }}"
    data-csrf-token="{{ csrf_token() }}"
    data-can-edit="{{ $canEdit ? '1' : '0' }}"
></div>

@push('scripts')
    @vite('resources/js/islands/persons-editor.js')
@endpush
