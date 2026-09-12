@extends('layouts.guest')

@section('title', __('Lupa kata sandi'))

@section('content')
    <p class="login-box-msg">
        {{ __('Masukkan email Anda dan kami kirim tautan untuk mengatur ulang kata sandi.') }}
    </p>

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-3">
            <div class="input-group">
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                       class="form-control @error('email') is-invalid @enderror"
                       placeholder="{{ __('Email') }}" required autofocus autocomplete="username">
                <div class="input-group-text"><span class="bi bi-envelope"></span></div>
            </div>
            @error('email')<p class="text-danger small mb-0 mt-1">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="btn btn-primary w-100">{{ __('Kirim tautan') }}</button>
    </form>

    <p class="mt-3 mb-0">
        <a href="{{ route('login') }}">{{ __('Kembali ke halaman masuk') }}</a>
    </p>
@endsection
