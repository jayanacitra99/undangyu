@extends('layouts.client')

@section('title', __('Dashboard'))

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Halo, :name', ['name' => auth()->user()->name]) }}</h3>
        </div>
        <div class="card-body">
            <p>{{ __('Belum ada undangan. Modul undangan mendarat di Sesi 12.') }}</p>
        </div>
    </div>
@endsection
