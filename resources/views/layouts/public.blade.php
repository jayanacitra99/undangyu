{{--
    The Tailwind shell for public marketing pages such as the template gallery
    (docs/05 § 2). Bootstrap and AdminLTE never load here.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('meta_description', __('Undangan digital multi-acara: pernikahan, ulang tahun, aqiqah dan lainnya.'))">

    <title>@yield('title') · {{ config('app.name') }}</title>

    @vite(['resources/css/public.css', 'resources/js/public.js'])
    @stack('head')
</head>
<body class="h-full bg-stone-50 text-stone-800 antialiased">
    <header class="border-b border-stone-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
            <a href="{{ url('/') }}" class="text-lg font-semibold tracking-tight">{{ config('app.name') }}</a>
            <nav class="flex items-center gap-6 text-sm">
                <a href="{{ route('templates.index') }}" class="hover:text-stone-950">{{ __('Template') }}</a>
                <a href="{{ route('login') }}" class="rounded-md bg-stone-900 px-3 py-2 text-white hover:bg-stone-700">
                    {{ __('Masuk') }}
                </a>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-10">
        @yield('content')
    </main>

    <footer class="border-t border-stone-200 bg-white">
        <div class="mx-auto max-w-6xl px-4 py-6 text-sm text-stone-500">
            &copy; {{ date('Y') }} {{ config('app.name') }}
        </div>
    </footer>
</body>
</html>
