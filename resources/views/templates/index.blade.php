@extends('layouts.public')

@section('title', __('Galeri Template'))

@section('content')
    <div class="mb-8">
        <h1 class="text-3xl font-semibold tracking-tight">{{ __('Galeri Template') }}</h1>
        <p class="mt-2 text-stone-600">
            {{ __('Pilih desain, lalu sesuaikan warna, font dan isinya dari dashboard.') }}
        </p>
    </div>

    <form method="GET" action="{{ route('templates.index') }}" class="mb-8 grid gap-3 sm:grid-cols-4">
        <div>
            <label for="event_type" class="mb-1 block text-sm font-medium">{{ __('Jenis acara') }}</label>
            <select id="event_type" name="event_type"
                    class="w-full rounded-md border-stone-300 text-sm focus:border-stone-500 focus:ring-stone-500">
                <option value="">{{ __('Semua acara') }}</option>
                @foreach ($eventTypes as $eventType)
                    <option value="{{ $eventType['slug'] }}" @selected($filters['event_type'] === $eventType['slug'])>
                        {{ $eventType['name'] }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="category" class="mb-1 block text-sm font-medium">{{ __('Kategori') }}</label>
            <select id="category" name="category"
                    class="w-full rounded-md border-stone-300 text-sm focus:border-stone-500 focus:ring-stone-500">
                <option value="">{{ __('Semua kategori') }}</option>
                @foreach ($categories as $category)
                    <option value="{{ $category['slug'] }}" @selected($filters['category'] === $category['slug'])>
                        {{ $category['name'] }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="tier" class="mb-1 block text-sm font-medium">{{ __('Tier') }}</label>
            <select id="tier" name="tier"
                    class="w-full rounded-md border-stone-300 text-sm focus:border-stone-500 focus:ring-stone-500">
                <option value="">{{ __('Semua tier') }}</option>
                <option value="free" @selected($filters['tier'] === 'free')>{{ __('Termasuk paket') }}</option>
                <option value="premium" @selected($filters['tier'] === 'premium')>{{ __('Premium') }}</option>
            </select>
        </div>

        <div class="flex items-end gap-2">
            <button type="submit" class="rounded-md bg-stone-900 px-4 py-2 text-sm text-white hover:bg-stone-700">
                {{ __('Terapkan') }}
            </button>
            <a href="{{ route('templates.index') }}" class="px-2 py-2 text-sm text-stone-600 hover:text-stone-900">
                {{ __('Reset') }}
            </a>
        </div>
    </form>

    @if ($templates->isEmpty())
        <p class="rounded-md border border-dashed border-stone-300 p-10 text-center text-stone-500">
            {{ __('Belum ada template yang cocok dengan filter ini.') }}
        </p>
    @else
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($templates as $template)
                <article class="overflow-hidden rounded-lg border border-stone-200 bg-white transition hover:shadow-md">
                    <a href="{{ route('templates.show', $template['slug']) }}">
                        <img src="{{ $template['thumbnail_url'] }}"
                             alt="{{ $template['name'] }}" loading="lazy"
                             class="aspect-[3/4] w-full bg-stone-100 object-cover">
                    </a>
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-2">
                            <h2 class="font-medium">
                                <a href="{{ route('templates.show', $template['slug']) }}" class="hover:underline">
                                    {{ $template['name'] }}
                                </a>
                            </h2>
                            @if ($template['is_premium'])
                                <span class="rounded bg-amber-100 px-2 py-0.5 text-xs text-amber-800">{{ __('Premium') }}</span>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-stone-500">{{ $template['category'] }}</p>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-10">
            {{ $templates->links() }}
        </div>
    @endif
@endsection
