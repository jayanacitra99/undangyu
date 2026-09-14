@extends('layouts.admin')

@section('title', $template->name)

@section('content')
    <form method="POST" action="{{ route('admin.templates.update', $template) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.templates._form', ['submitLabel' => __('Perbarui')])
    </form>
@endsection
