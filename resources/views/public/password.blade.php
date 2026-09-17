{{--
    The passphrase gate (21.4).

    Shows the invitation's title and nothing else: a guest who cannot open it
    yet should still see they are in the right place, and someone who guessed
    the slug should learn nothing about the event.
--}}
<!DOCTYPE html>
<html lang="{{ $payload['invitation']['language'] }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ $payload['invitation']['meta']['title'] }}</title>

    @vite('resources/css/public.css')
</head>
<body class="flex h-full items-center justify-center bg-stone-50 px-6 text-stone-800 antialiased">
    <main class="w-full max-w-sm text-center">
        <h1 class="text-2xl font-semibold tracking-tight">{{ $payload['invitation']['title'] }}</h1>
        <p class="mt-2 text-stone-600">{{ __('Undangan ini dilindungi kata sandi.') }}</p>

        <form method="POST" action="{{ route('invitation.unlock', ['slug' => $slug]) }}" class="mt-6">
            @csrf

            <label for="password" class="sr-only">{{ __('Kata sandi') }}</label>
            <input
                id="password"
                type="password"
                name="password"
                autocomplete="off"
                autofocus
                required
                class="w-full rounded-md border-stone-300 text-center shadow-sm focus:border-stone-500 focus:ring-stone-500"
                placeholder="{{ __('Kata sandi') }}"
            >

            @error('password')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror

            <button type="submit" class="mt-4 w-full rounded-md bg-stone-900 px-4 py-2 text-white hover:bg-stone-700">
                {{ __('Buka undangan') }}
            </button>
        </form>

        <p class="mt-6 text-sm text-stone-400">
            {{ __('Minta kata sandi kepada pemilik acara.') }}
        </p>
    </main>
</body>
</html>
