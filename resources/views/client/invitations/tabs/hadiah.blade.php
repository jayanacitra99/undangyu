{{--
    "Hadiah" tab (19.1) — the GiftsEditor island.
--}}
<div
    data-island="gifts-editor"
    data-gifts="{{ json_encode($gifts) }}"
    data-types="{{ json_encode($giftTypes) }}"
    data-store-url="{{ route('api.gifts.store', $invitation) }}"
    data-reorder-url="{{ route('api.gifts.reorder', $invitation) }}"
    data-item-url-template="{{ route('api.gifts.update', ['gift' => '__ID__']) }}"
    data-image-url-template="{{ route('api.gifts.image', ['gift' => '__ID__']) }}"
    data-csrf-token="{{ csrf_token() }}"
    data-can-edit="{{ $canEdit ? '1' : '0' }}"
></div>

@push('scripts')
    @vite('resources/js/islands/gifts-editor.js')
@endpush
