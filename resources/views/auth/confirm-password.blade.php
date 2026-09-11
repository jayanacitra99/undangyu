@extends('layouts.guest')

@section('title', __('Konfirmasi kata sandi'))

@section('content')
    <p class="login-box-msg">
        {{ __('Ini area aman. Konfirmasi kata sandi Anda sebelum melanjutkan.') }}
    </p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="mb-3">
            <div class="input-group">
                <input id="password" type="password" name="password"
                       class="form-control @error('password') is-invalid @enderror"
                       placeholder="{{ __('Kata sandi') }}" required autofocus autocomplete="current-password">
                <div class="input-group-text"><span class="bi bi-lock-fill"></span></div>
            </div>
            @error('password')<p class="text-danger small mb-0 mt-1">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="btn btn-primary w-100">{{ __('Konfirmasi') }}</button>
    </form>
@endsection
