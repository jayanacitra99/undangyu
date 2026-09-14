@extends('layouts.admin')

@section('title', __('Ubah jenis acara'))

@section('content')
    <form method="POST" action="{{ route('admin.event-types.update', $eventType) }}">
        @csrf
        @method('put')
        @include('admin.event-types._form', ['submitLabel' => __('Simpan perubahan')])
    </form>
@endsection
