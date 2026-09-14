@extends('layouts.admin')

@section('title', __('Jenis acara baru'))

@section('content')
    <form method="POST" action="{{ route('admin.event-types.store') }}">
        @csrf
        @include('admin.event-types._form', ['submitLabel' => __('Simpan')])
    </form>
@endsection
