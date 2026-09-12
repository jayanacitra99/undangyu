{{--
    The AdminLTE 4 shell every dashboard page extends (docs/05 § 2).

    AdminLTE owns this chrome — navbar, sidebar, wrapper, footer — and its JS
    reads the DOM at load. Vue only ever mounts into a dedicated container div
    inside @section('content'), pushed with its own island entry.

    Child layouts pass:
        $menuKey    which config/menu.php list the sidebar renders
        $bodyClass  extra body classes (the client layout collapses the sidebar)
--}}
@php
    $menuKey ??= App\Support\Menu::keyFor(auth()->user());
    $bodyClass ??= '';
    $menu = App\Support\Menu::for($menuKey, auth()->user());
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
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary {{ $bodyClass }}">
<div class="app-wrapper">
    @include('layouts.partials.navbar')
    @include('layouts.partials.sidebar', ['menu' => $menu])

    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <h1 class="mb-0 h3">@yield('title', config('app.name'))</h1>
                    </div>
                    <div class="col-sm-6">
                        @hasSection('breadcrumb')
                            <ol class="breadcrumb float-sm-end mb-0">
                                @yield('breadcrumb')
                            </ol>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="app-content">
            <div class="container-fluid">
                @include('layouts.partials.alerts')
                @yield('content')
            </div>
        </div>
    </main>

    @include('layouts.partials.footer')
</div>
@stack('scripts')
</body>
</html>
