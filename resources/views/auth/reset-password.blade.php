@extends('layouts.guest')

@section('title', __('Atur ulang kata sandi'))

@section('content')
    <p class="login-box-msg">{{ __('Pilih kata sandi baru') }}</p>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="mb-3">
            <div class="input-group">
                <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}"
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
                       placeholder="{{ __('Kata sandi baru') }}" required autocomplete="new-password">
                <div class="input-group-text"><span class="bi bi-lock-fill"></span></div>
            </div>
            @error('password')<p class="text-danger small mb-0 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mb-3">
            <div class="input-group">
                <input id="password_confirmation" type="password" name="password_confirmation"
                       class="form-control @error('password_confirmation') is-invalid @enderror"
                       placeholder="{{ __('Ulangi kata sandi baru') }}" required autocomplete="new-password">
                <div class="input-group-text"><span class="bi bi-lock-fill"></span></div>
            </div>
            @error('password_confirmation')<p class="text-danger small mb-0 mt-1">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="btn btn-primary w-100">{{ __('Simpan kata sandi') }}</button>
    </form>
@endsection
