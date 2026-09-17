@php use App\Support\Money; @endphp
@extends('layouts.public')

@section('title', __('Harga'))
@section('meta_description', __('Paket undangan digital Undangyu beserta perbandingan fiturnya.'))

@section('content')
    <div class="mb-8">
        <h1 class="text-3xl font-semibold tracking-tight">{{ __('Harga') }}</h1>
        <p class="mt-2 text-stone-600">
            {{ __('Satu kali bayar per undangan. Tidak ada langganan bulanan.') }}
        </p>
    </div>

    @if ($packages === [])
        <p class="rounded-md border border-dashed border-stone-300 p-10 text-center text-stone-500">
            {{ __('Paket belum tersedia.') }}
        </p>
    @else
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($packages as $package)
                <article @class([
                    'rounded-lg border bg-white p-5',
                    'border-stone-900 ring-1 ring-stone-900' => $package['is_featured'],
                    'border-stone-200' => ! $package['is_featured'],
                ])>
                    @if ($package['is_featured'])
                        <span class="mb-2 inline-block rounded bg-stone-900 px-2 py-0.5 text-xs text-white">
                            {{ __('Paling populer') }}
                        </span>
                    @endif

                    <h2 class="text-lg font-semibold">{{ $package['name'] }}</h2>

                    <p class="mt-2">
                        <span class="text-2xl font-semibold">
                            {{ Money::idr($package['effective_price']) }}
                        </span>
                        @if ($package['has_discount'])
                            <span class="ml-2 text-sm text-stone-400 line-through">
                                {{ Money::idr($package['price']) }}
                            </span>
                        @endif
                    </p>

                    <p class="mt-1 text-sm text-stone-500">
                        {{ __('Aktif :days hari', ['days' => $package['active_days']]) }}
                    </p>

                    @if ($package['description'])
                        <p class="mt-3 text-sm text-stone-600">{{ $package['description'] }}</p>
                    @endif

                    {{-- A guest is sent to login and returned here afterwards. --}}
                    <a href="{{ route('checkout.show', ['package' => $package['slug']]) }}"
                       class="mt-5 block rounded-md bg-stone-900 px-4 py-2 text-center text-sm text-white hover:bg-stone-700">
                        {{ __('Pilih paket') }}
                    </a>
                </article>
            @endforeach
        </div>

        <h2 class="mt-14 mb-4 text-xl font-semibold tracking-tight">{{ __('Perbandingan fitur') }}</h2>

        <div class="overflow-x-auto rounded-lg border border-stone-200 bg-white">
            <table class="w-full min-w-[40rem] text-sm">
                <thead>
                    <tr class="border-b border-stone-200 text-left">
                        <th scope="col" class="p-3 font-medium">{{ __('Fitur') }}</th>
                        @foreach ($packages as $package)
                            <th scope="col" class="p-3 font-medium">{{ $package['name'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr class="border-b border-stone-100 last:border-0">
                            <th scope="row" class="p-3 text-left font-normal text-stone-600">{{ $row['label'] }}</th>
                            @foreach ($row['values'] as $value)
                                <td class="p-3">
                                    @if ($value === null)
                                        {{ __('Tanpa batas') }}
                                    @elseif ($row['is_quota'])
                                        {{ number_format((int) $value, 0, ',', '.') }}
                                    @elseif ($value)
                                        <span class="text-emerald-700">&check;</span>
                                        <span class="sr-only">{{ __('Termasuk') }}</span>
                                    @else
                                        <span class="text-stone-300">&mdash;</span>
                                        <span class="sr-only">{{ __('Tidak termasuk') }}</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
