@extends('layouts.admin')

@section('title', __('Dashboard'))

@section('content')
    <div class="row g-3">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="small-box text-bg-primary">
                <div class="inner">
                    <h3>&mdash;</h3>
                    <p>{{ __('Undangan aktif') }}</p>
                </div>
                <i class="small-box-icon bi bi-envelope-heart"></i>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="small-box text-bg-success">
                <div class="inner">
                    <h3>&mdash;</h3>
                    <p>{{ __('Pesanan bulan ini') }}</p>
                </div>
                <i class="small-box-icon bi bi-receipt"></i>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="small-box text-bg-warning">
                <div class="inner">
                    <h3>&mdash;</h3>
                    <p>{{ __('Menunggu verifikasi') }}</p>
                </div>
                <i class="small-box-icon bi bi-hourglass-split"></i>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="small-box text-bg-secondary">
                <div class="inner">
                    <h3>&mdash;</h3>
                    <p>{{ __('Klien terdaftar') }}</p>
                </div>
                <i class="small-box-icon bi bi-people"></i>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Selamat datang') }}</h3>
        </div>
        <div class="card-body">
            <p class="mb-0">
                {{ __('Panel admin Undangyu. Angka di atas terisi saat modul pesanan dan undangan mendarat.') }}
            </p>
        </div>
    </div>
@endsection
