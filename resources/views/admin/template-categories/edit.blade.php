@extends('layouts.admin')

@section('title', __('Ubah kategori'))

@section('content')
    <form method="POST" action="{{ route('admin.template-categories.update', $category) }}">
        @csrf
        @method('put')
        @include('admin.template-categories._form', ['submitLabel' => __('Simpan perubahan')])
    </form>
@endsection
