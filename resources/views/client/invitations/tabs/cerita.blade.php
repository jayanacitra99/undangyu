{{--
    "Cerita" tab (19.2) — the StoryEditor island.
--}}
<div
    data-island="story-editor"
    data-stories="{{ json_encode($stories) }}"
    data-store-url="{{ route('api.stories.store', $invitation) }}"
    data-reorder-url="{{ route('api.stories.reorder', $invitation) }}"
    data-item-url-template="{{ route('api.stories.update', ['story' => '__ID__']) }}"
    data-image-url-template="{{ route('api.stories.image', ['story' => '__ID__']) }}"
    data-csrf-token="{{ csrf_token() }}"
    data-can-edit="{{ $canEdit ? '1' : '0' }}"
></div>

@push('scripts')
    @vite('resources/js/islands/story-editor.js')
@endpush
