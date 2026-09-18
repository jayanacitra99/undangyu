{{--
    The published invitation shell (21.3).

    **The <head> is server-rendered and that is the point.** WhatsApp, Facebook
    and Twitter fetch a shared link with a scraper that does not execute
    JavaScript. If these tags were written by Vue, every invitation anyone
    shares — which is the entire product — would preview as a bare URL.

    The body below is a server-rendered summary that the Vue app replaces once
    it mounts. It is there so the page says something before the bundle lands,
    and so a crawler with no JavaScript still reads the couple's names.
--}}
@php
    $invitation = $payload['invitation'];
    $meta = $invitation['meta'];
    $firstEvent = $payload['events'][0] ?? null;
    $description = $meta['description']
        ?? ($firstEvent !== null
            ? __(':title · :venue', ['title' => $invitation['title'], 'venue' => $firstEvent['venue_name']])
            : $invitation['title']);
    $canonical = url('/'.$invitation['slug']);

    // Whatever the resolved theme asked for, de-duplicated. A template with no
    // font keys in its schema requests nothing.
    $fontFamilies = collect($payload['theme']['fonts'] ?? [])
        ->filter(fn ($family): bool => is_string($family) && $family !== '')
        ->unique()
        ->values()
        ->all();
@endphp
<!DOCTYPE html>
<html lang="{{ $invitation['language'] }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $meta['title'] }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $canonical }}">

    {{-- An unlisted or password-gated invitation stays out of search results. --}}
    @if ($invitation['visibility'] !== 'public' || $isPreview)
        <meta name="robots" content="noindex, nofollow">
    @endif

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:title" content="{{ $meta['title'] }}">
    <meta property="og:description" content="{{ $description }}">
    @if ($meta['og_image'])
        <meta property="og:image" content="{{ $meta['og_image'] }}">
        {{-- The dimensions let WhatsApp lay the card out before the image
             finishes downloading, which is most of the "preview appeared
             instantly" feeling (M12.7 generates them at this size). --}}
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="{{ $meta['title'] }}">
    @endif

    <meta name="twitter:card" content="{{ $meta['og_image'] ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $meta['title'] }}">
    <meta name="twitter:description" content="{{ $description }}">
    @if ($meta['og_image'])
        <meta name="twitter:image" content="{{ $meta['og_image'] }}">
    @endif

    {{--
        The template's chosen typefaces, requested by name from the theme the
        payload already resolved. `display=swap` so text paints in a fallback
        immediately rather than holding the largest element back — the cover
        headline is the LCP.
    --}}
    @if ($fontFamilies !== [])
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link
            rel="stylesheet"
            href="https://fonts.googleapis.com/css2?{{ collect($fontFamilies)->map(fn (string $family): string => 'family='.str_replace(' ', '+', $family).':wght@400;500;600')->implode('&') }}&display=swap"
        >
    @endif

    @vite(['resources/css/public.css', 'resources/js/public.js'])
</head>
<body class="h-full bg-stone-50 text-stone-800 antialiased">
    @if ($isPreview)
        <div class="bg-amber-100 px-4 py-2 text-center text-sm text-amber-900">
            {{ $previewNotice ?? __('Pratinjau.') }}
        </div>
    @endif

    {{--
        The guest this link was sent to (27.2), or nothing at all. The preview
        route renders this same view without one, hence the default — and a
        shared link has no token by definition.
    --}}
    <div id="invitation" data-payload="{{ json_encode($payload) }}"
         @if (($guest ?? null) !== null) data-guest="{{ json_encode($guest) }}" @endif>
        {{--
            Replaced by the Vue app on mount. The template components land in
            Session 22; until then this is also what the page looks like.
        --}}
        <main class="mx-auto max-w-2xl px-6 py-16 text-center">
            @if (($guest ?? null) !== null)
                <p class="text-sm text-stone-500">
                    {{ __('Kepada Yth. :name', ['name' => $guest['name']]) }}
                </p>
            @endif

            <h1 class="text-3xl font-semibold tracking-tight">{{ $invitation['title'] }}</h1>

            @foreach ($payload['events'] as $event)
                <section class="mt-8">
                    <h2 class="text-lg font-medium">{{ $event['name'] }}</h2>
                    <p class="mt-1 text-stone-600">
                        {{ \Illuminate\Support\Carbon::parse($event['start_at'])->translatedFormat('l, d F Y · H:i') }}
                    </p>
                    <p class="text-stone-600">{{ $event['venue_name'] }}</p>
                    @if ($event['address'])
                        <p class="text-sm text-stone-500">{{ $event['address'] }}</p>
                    @endif
                </section>
            @endforeach

            @if ($payload['persons'] !== [])
                <p class="mt-10 text-stone-600">
                    {{ collect($payload['persons'])->pluck('display_name')->join(' & ') }}
                </p>
            @endif
        </main>
    </div>
</body>
</html>
