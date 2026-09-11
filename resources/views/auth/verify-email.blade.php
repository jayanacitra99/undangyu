@extends('layouts.guest')

@section('title', __('Verifikasi email'))

@section('content')
    <p class="login-box-msg">
        {{ __('Terima kasih sudah mendaftar. Klik tautan yang kami kirim ke email Anda untuk memverifikasi alamatnya.') }}
    </p>

    @if (session('status') === 'verification-link-sent')
        <div class="alert alert-success" role="alert">
            {{ __('Tautan verifikasi baru sudah dikirim ke email Anda.') }}
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn btn-primary w-100">{{ __('Kirim ulang email verifikasi') }}</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-3">
        @csrf
        <button type="submit" class="btn btn-link p-0">{{ __('Keluar') }}</button>
    </form>
@endsection
