@php use App\Support\Money; @endphp
@extends('layouts.public')

@section('title', $template['name'])
@section('meta_description', $template['description'] ?: __('Template undangan digital :name.', ['name' => $template['name']]))

@section('content')
    <nav class="mb-6 text-sm text-stone-500">
        <a href="{{ route('templates.index') }}" class="hover:text-stone-900">{{ __('Galeri Template') }}</a>
        <span class="px-2">/</span>
        <span class="text-stone-800">{{ $template['name'] }}</span>
    </nav>

    <div class="grid gap-10 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <img src="{{ $template['thumbnail_url'] }}" alt="{{ $template['name'] }}"
                 class="w-full rounded-lg border border-stone-200 bg-stone-100 object-cover">

            @if ($template['screenshots'] !== [])
                <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3">
                    @foreach ($template['screenshots'] as $screenshot)
                        <figure>
                            <img src="{{ $screenshot['url'] }}"
                                 alt="{{ $screenshot['caption'] ?: $template['name'] }}" loading="lazy"
                                 class="aspect-[3/4] w-full rounded-md border border-stone-200 bg-stone-100 object-cover">
                            @if ($screenshot['caption'])
                                <figcaption class="mt-1 text-xs text-stone-500">{{ $screenshot['caption'] }}</figcaption>
                            @endif
                        </figure>
                    @endforeach
                </div>
            @endif
        </div>

        <aside>
            <h1 class="text-2xl font-semibold tracking-tight">{{ $template['name'] }}</h1>
            <p class="mt-1 text-sm text-stone-500">
                {{ $template['category'] }} · {{ __('versi') }} {{ $template['version'] }}
            </p>

            @if ($template['description'])
                <p class="mt-4 text-stone-700">{{ $template['description'] }}</p>
            @endif

            <dl class="mt-6 space-y-3 text-sm">
                <div>
                    <dt class="text-stone-500">{{ __('Cocok untuk') }}</dt>
                    <dd class="mt-1 flex flex-wrap gap-1">
                        @foreach ($template['event_types'] as $eventTypeName)
                            <span class="rounded bg-stone-100 px-2 py-0.5">{{ $eventTypeName }}</span>
                        @endforeach
                    </dd>
                </div>
                <div>
                    <dt class="text-stone-500">{{ __('Tier') }}</dt>
                    <dd class="mt-1">
                        @if ($template['is_premium'])
                            {{ __('Premium') }} · {{ Money::idr($template['extra_price']) }}
                        @else
                            {{ __('Termasuk dalam paket') }}
                        @endif
                    </dd>
                </div>
            </dl>

            <div class="mt-8 flex flex-col gap-2">
                {{-- The live preview route lands in Session 21; the button is a placeholder until then. --}}
                <button type="button" disabled
                        class="cursor-not-allowed rounded-md bg-stone-200 px-4 py-2 text-sm text-stone-500">
                    {{ __('Pratinjau (segera)') }}
                </button>
                <a href="{{ route('register') }}"
                   class="rounded-md bg-stone-900 px-4 py-2 text-center text-sm text-white hover:bg-stone-700">
                    {{ __('Pakai template ini') }}
                </a>
            </div>
        </aside>
    </div>
@endsection
