{{--
    "Galeri" tab (18.4) — the GalleryEditor island.

    The quota goes in as data rather than being counted client-side: the
    FormRequest enforces the same numbers, and there is one place that decides
    what the package allows.
--}}
<div
    data-island="gallery-editor"
    data-media="{{ json_encode($media) }}"
    data-quota="{{ json_encode($mediaQuota) }}"
    data-tracks="{{ json_encode($audioLibrary) }}"
    data-can-use-music="{{ $canUseMusic ? '1' : '0' }}"
    data-upload-url="{{ route('api.media.store', $invitation) }}"
    data-embedded-url="{{ route('api.media.embedded', $invitation) }}"
    data-reorder-url="{{ route('api.media.reorder', $invitation) }}"
    data-item-url-template="{{ route('api.media.update', ['media' => '__ID__']) }}"
    data-cover-url-template="{{ route('api.media.cover', ['media' => '__ID__']) }}"
    data-max-upload-mb="{{ $maxUploadMb }}"
    data-csrf-token="{{ csrf_token() }}"
    data-can-edit="{{ $canEdit ? '1' : '0' }}"
></div>

@push('scripts')
    @vite('resources/js/islands/gallery-editor.js')
@endpush
