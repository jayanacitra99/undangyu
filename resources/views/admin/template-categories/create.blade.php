@extends('layouts.admin')

@section('title', __('Kategori baru'))

@section('content')
    <form method="POST" action="{{ route('admin.template-categories.store') }}">
        @csrf
        @include('admin.template-categories._form', ['submitLabel' => __('Simpan')])
    </form>
@endsection
