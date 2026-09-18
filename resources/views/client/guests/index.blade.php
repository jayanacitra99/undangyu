{{--
    Guest list (M5.1, M5.10) — the GuestTable island.

    A page of its own rather than a builder tab: this is a paginated table with
    bulk selection over a thousand rows, and the builder's tabs are autosaved
    forms over a handful.
--}}
@extends('layouts.client')

@section('title', __('Tamu') . ' — ' . $invitation->title)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('client.invitations.index') }}">{{ __('Undangan') }}</a></li>
    <li class="breadcrumb-item">
        <a href="{{ route('client.invitations.edit', $invitation) }}">{{ $invitation->title }}</a>
    </li>
    <li class="breadcrumb-item active">{{ __('Tamu') }}</li>
@endsection

@section('content')
    @unless ($canEdit)
        <div class="alert alert-warning" role="alert">
            <i class="bi bi-lock me-1"></i>
            {{ __('Undangan ini sedang ditangguhkan, jadi daftar tamu hanya bisa dilihat.') }}
        </div>
    @endunless

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h3 class="card-title mb-0"><i class="bi bi-people me-1"></i>{{ __('Daftar tamu') }}</h3>
            <a href="{{ route('client.invitations.edit', $invitation) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-pencil-square me-1"></i>{{ __('Kembali ke builder') }}
            </a>
        </div>
        <div class="card-body">
            <div
                data-island="guest-table"
                data-guests="{{ json_encode($guests) }}"
                data-pagination="{{ json_encode($pagination) }}"
                data-groups="{{ json_encode($groups) }}"
                data-quota="{{ json_encode($quota) }}"
                data-titles="{{ json_encode($titles) }}"
                data-invitation-url="{{ route('invitation.show', ['slug' => $invitation->slug]) }}"
                data-index-url="{{ route('api.guests.index', $invitation) }}"
                data-store-url="{{ route('api.guests.store', $invitation) }}"
                data-item-url-template="{{ route('api.guests.update', ['guest' => '__ID__']) }}"
                data-bulk-delete-url="{{ route('api.guests.bulk-delete', $invitation) }}"
                data-bulk-group-url="{{ route('api.guests.bulk-group', $invitation) }}"
                data-group-store-url="{{ route('api.guest-groups.store', $invitation) }}"
                data-group-item-url-template="{{ route('api.guest-groups.update', ['group' => '__ID__']) }}"
                data-latest-import="{{ $latestImport === null ? '' : json_encode($latestImport) }}"
                data-template-url="{{ route('client.invitations.guests.template', $invitation) }}"
                data-template-csv-url="{{ route('client.invitations.guests.template', [$invitation, 'format' => 'csv']) }}"
                data-export-url="{{ route('client.invitations.guests.export', $invitation) }}"
                data-import-upload-url="{{ route('api.guest-imports.store', $invitation) }}"
                data-import-status-url-template="{{ route('api.guest-imports.show', ['import' => '__ID__']) }}"
                data-import-errors-url-template="{{ route('client.guest-imports.errors', ['import' => '__ID__']) }}"
                data-csrf-token="{{ csrf_token() }}"
                data-can-edit="{{ $canEdit ? '1' : '0' }}"
            ></div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/islands/guest-table.js')
@endpush
