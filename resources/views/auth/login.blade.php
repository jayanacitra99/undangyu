@extends('layouts.guest')

@section('title', __('Masuk'))

@section('content')
    <p class="login-box-msg">{{ __('Masuk untuk mengelola undangan Anda') }}</p>

    <form method="POST" action="{{ route('login') }}">
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

        <div class="mb-3">
            <div class="input-group">
                <input id="password" type="password" name="password"
                       class="form-control @error('password') is-invalid @enderror"
                       placeholder="{{ __('Kata sandi') }}" required autocomplete="current-password">
                <div class="input-group-text"><span class="bi bi-lock-fill"></span></div>
            </div>
            @error('password')<p class="text-danger small mb-0 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="row align-items-center">
            <div class="col-7">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember_me">
                    <label class="form-check-label" for="remember_me">{{ __('Ingat saya') }}</label>
                </div>
            </div>
            <div class="col-5 text-end">
                <button type="submit" class="btn btn-primary">{{ __('Masuk') }}</button>
            </div>
        </div>
    </form>

    <p class="mt-3 mb-1">
        @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}">{{ __('Lupa kata sandi?') }}</a>
        @endif
    </p>
    <p class="mb-0">
        <a href="{{ route('register') }}" class="text-center">{{ __('Daftar akun baru') }}</a>
    </p>
@endsection
