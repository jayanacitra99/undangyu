@extends('layouts.admin')

@section('title', __('Template baru'))

@section('content')
    <form method="POST" action="{{ route('admin.templates.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.templates._form', ['submitLabel' => __('Simpan')])
    </form>
@endsection
