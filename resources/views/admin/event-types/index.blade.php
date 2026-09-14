@extends('layouts.admin')

@section('title', __('Jenis Acara'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="text-secondary mb-0">
            {{ __('Geser baris untuk mengubah urutan tampil. Jenis nonaktif tidak muncul di pilihan klien.') }}
        </p>
        <a href="{{ route('admin.event-types.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> {{ __('Jenis acara baru') }}
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div
                data-island="sortable-rows"
                data-columns='@json($columns)'
                data-rows='@json($rows)'
                data-reorder-url="{{ route('admin.event-types.reorder') }}"
                data-edit-url-template="{{ route('admin.event-types.edit', ['event_type' => '__ID__']) }}"
                data-delete-url-template="{{ route('admin.event-types.destroy', ['event_type' => '__ID__']) }}"
                data-csrf-token="{{ csrf_token() }}"
                data-delete-confirm="{{ __('Hapus jenis acara ini?') }}"
            ></div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/islands/sortable-rows.js')
@endpush
