@extends('layouts.guest', ['pageClass' => 'register-page', 'boxClass' => 'register-box'])

@section('title', __('Daftar'))

@section('content')
    <p class="register-box-msg">{{ __('Buat akun untuk mulai membuat undangan') }}</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="mb-3">
            <div class="input-group">
                <input id="name" type="text" name="name" value="{{ old('name') }}"
                       class="form-control @error('name') is-invalid @enderror"
                       placeholder="{{ __('Nama lengkap') }}" required autofocus autocomplete="name">
                <div class="input-group-text"><span class="bi bi-person"></span></div>
            </div>
            @error('name')<p class="text-danger small mb-0 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-3">
            <div class="input-group">
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                       class="form-control @error('email') is-invalid @enderror"
                       placeholder="{{ __('Email') }}" required autocomplete="username">
                <div class="input-group-text"><span class="bi bi-envelope"></span></div>
            </div>
            @error('email')<p class="text-danger small mb-0 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-3">
            <div class="input-group">
                <input id="phone" type="tel" name="phone" value="{{ old('phone') }}"
                       class="form-control @error('phone') is-invalid @enderror"
                       placeholder="{{ __('Nomor WhatsApp, contoh 0812-3456-7890') }}" required autocomplete="tel">
                <div class="input-group-text"><span class="bi bi-whatsapp"></span></div>
            </div>
            @error('phone')<p class="text-danger small mb-0 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-3">
            <div class="input-group">
                <input id="password" type="password" name="password"
                       class="form-control @error('password') is-invalid @enderror"
                       placeholder="{{ __('Kata sandi') }}" required autocomplete="new-password">
                <div class="input-group-text"><span class="bi bi-lock-fill"></span></div>
            </div>
            @error('password')<p class="text-danger small mb-0 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-3">
            <div class="input-group">
                <input id="password_confirmation" type="password" name="password_confirmation"
                       class="form-control @error('password_confirmation') is-invalid @enderror"
                       placeholder="{{ __('Ulangi kata sandi') }}" required autocomplete="new-password">
                <div class="input-group-text"><span class="bi bi-lock-fill"></span></div>
            </div>
            @error('password_confirmation')<p class="text-danger small mb-0 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="row">
            <div class="col-12">
                <button type="submit" class="btn btn-primary w-100">{{ __('Daftar') }}</button>
            </div>
        </div>
    </form>

    <p class="mt-3 mb-0">
        <a href="{{ route('login') }}" class="text-center">{{ __('Sudah punya akun? Masuk') }}</a>
    </p>
@endsection
