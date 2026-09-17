@extends('layouts.client')

@section('title', __('Dashboard'))

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Halo, :name', ['name' => auth()->user()->name]) }}</h3>
        </div>
        <div class="card-body">
            <p>{{ __('Kelola undangan Anda, atau ambil paket baru untuk membuat undangan.') }}</p>
            <a href="{{ route('client.invitations.index') }}" class="btn btn-primary">
                <i class="bi bi-envelope-heart me-1"></i>{{ __('Undangan saya') }}
            </a>
            <a href="{{ route('pricing.index') }}" class="btn btn-outline-secondary">{{ __('Lihat paket') }}</a>
        </div>
    </div>
@endsection
