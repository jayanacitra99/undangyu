@extends('layouts.admin')

@section('title', __('Paket baru'))

@section('content')
    <form method="POST" action="{{ route('admin.packages.store') }}">
        @csrf
        @include('admin.packages._form', ['submitLabel' => __('Simpan')])
    </form>
@endsection

@push('scripts')
    @vite('resources/js/islands/feature-flags.js')
@endpush
