@extends('layouts.admin')

@section('title', __('Kategori Template'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="text-secondary mb-0">
            {{ __('Geser baris untuk mengubah urutan tampil di katalog.') }}
        </p>
        <a href="{{ route('admin.template-categories.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> {{ __('Kategori baru') }}
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div
                data-island="sortable-rows"
                data-columns='@json($columns)'
                data-rows='@json($rows)'
                data-reorder-url="{{ route('admin.template-categories.reorder') }}"
                data-edit-url-template="{{ route('admin.template-categories.edit', ['template_category' => '__ID__']) }}"
                data-delete-url-template="{{ route('admin.template-categories.destroy', ['template_category' => '__ID__']) }}"
                data-csrf-token="{{ csrf_token() }}"
                data-delete-confirm="{{ __('Hapus kategori ini?') }}"
            ></div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/islands/sortable-rows.js')
@endpush
