{{-- One sidebar row. Recurses for treeview children. --}}
@php
    $active = App\Support\Menu::isActive($item);
    $children = $item['children'] ?? [];
@endphp

@if ($children !== [])
    <li class="nav-item {{ $active ? 'menu-open' : '' }}">
        <a href="#" class="nav-link {{ $active ? 'active' : '' }}">
            <i class="nav-icon bi {{ $item['icon'] }}"></i>
            <p>
                {{ __($item['label']) }}
                <i class="nav-arrow bi bi-chevron-right"></i>
            </p>
        </a>
        <ul class="nav nav-treeview">
            @foreach ($children as $child)
                @include('layouts.partials.menu-item', ['item' => $child])
            @endforeach
        </ul>
    </li>
@else
    <li class="nav-item">
        <a href="{{ route($item['route']) }}" class="nav-link {{ $active ? 'active' : '' }}">
            <i class="nav-icon bi {{ $item['icon'] }}"></i>
            <p>{{ __($item['label']) }}</p>
        </a>
    </li>
@endif
