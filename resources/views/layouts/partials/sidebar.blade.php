{{-- Items come from App\Support\Menu, already filtered by permission. --}}
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
        <a href="{{ App\Support\RoleHome::for(auth()->user()) }}" class="brand-link">
            <i class="bi bi-envelope-heart brand-image ms-3 me-2"></i>
            <span class="brand-text fw-light">{{ config('app.name') }}</span>
        </a>
    </div>

    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                @foreach ($menu as $item)
                    @include('layouts.partials.menu-item', ['item' => $item])
                @endforeach
            </ul>
        </nav>
    </div>
</aside>
