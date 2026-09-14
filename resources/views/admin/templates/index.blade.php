@extends('layouts.admin')

@section('title', __('Template'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="text-secondary mb-0">
            {{ __('Geser baris untuk mengubah urutan tampil di galeri publik.') }}
        </p>
        <a href="{{ route('admin.templates.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> {{ __('Template baru') }}
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div
                data-island="sortable-rows"
                data-columns='@json($columns)'
                data-rows='@json($rows)'
                data-reorder-url="{{ route('admin.templates.reorder') }}"
                data-edit-url-template="{{ route('admin.templates.edit', ['template' => '__ID__']) }}"
                data-delete-url-template="{{ route('admin.templates.destroy', ['template' => '__ID__']) }}"
                data-csrf-token="{{ csrf_token() }}"
                data-delete-confirm="{{ __('Hapus template ini?') }}"
            ></div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/islands/sortable-rows.js')
@endpush
