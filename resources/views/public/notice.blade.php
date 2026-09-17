{{--
    The standalone page a guest gets when an invitation is not open: ended,
    or suspended (21.2).

    Deliberately not a 404 and deliberately not the marketing layout. The link
    is sitting in five hundred WhatsApp threads and will be clicked for years;
    what it owes those guests is a sentence, not an error page or a sales page.
--}}
<!DOCTYPE html>
<html lang="{{ $payload['invitation']['language'] ?? 'id' }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ $heading }}</title>

    @vite('resources/css/public.css')
</head>
<body class="flex h-full items-center justify-center bg-stone-50 px-6 text-stone-800 antialiased">
    <main class="max-w-md text-center">

        <h1 class="text-2xl font-semibold tracking-tight">{{ $heading }}</h1>
        <p class="mt-3 text-stone-600">{{ $body }}</p>

        <p class="mt-8 text-sm text-stone-400">
            {{ $payload['invitation']['title'] }} · {{ config('app.name') }}
        </p>
    </main>
</body>
</html>
