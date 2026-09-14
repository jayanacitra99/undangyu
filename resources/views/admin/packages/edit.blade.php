@extends('layouts.admin')

@section('title', $package->name)

@section('content')
    <form method="POST" action="{{ route('admin.packages.update', $package) }}">
        @csrf
        @method('PUT')
        @include('admin.packages._form', ['submitLabel' => __('Perbarui')])
    </form>
@endsection

@push('scripts')
    @vite('resources/js/islands/feature-flags.js')
@endpush
