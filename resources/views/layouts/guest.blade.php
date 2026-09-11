{{--
    Shell for the signed-out pages — login, register, password reset.

    AdminLTE's own auth look: a centred card on a tinted body. It loads the
    dashboard bundle, since these pages lead into the dashboard and Tailwind
    never touches this side of the app.

    Pages pass:
        $pageClass  AdminLTE body class (login-page / register-page)
        $boxClass   card wrapper width (login-box / register-box)
--}}
@php
    $pageClass ??= 'login-page';
    $boxClass ??= 'login-box';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@hasSection('title')@yield('title') · @endif{{ config('app.name') }}</title>

    @vite(['resources/css/dashboard.css', 'resources/js/dashboard.js'])
    @stack('styles')
</head>
<body class="{{ $pageClass }} bg-body-secondary">
<div class="{{ $boxClass }}">
    <div class="card card-outline card-primary">
        <div class="card-header text-center">
            <a href="{{ url('/') }}" class="h1 text-decoration-none">
                <b>Undang</b>yu
            </a>
        </div>
        <div class="card-body">
            @if (session('status'))
                <div class="alert alert-success" role="alert">{{ session('status') }}</div>
            @endif

            @yield('content')
        </div>
    </div>
</div>
@stack('scripts')
</body>
</html>
