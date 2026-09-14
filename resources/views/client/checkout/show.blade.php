@extends('layouts.client')

@section('title', __('Checkout'))

@section('content')
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">{{ __('Ringkasan pesanan') }}</div>
                <div class="card-body">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('Item') }}</th>
                                <th scope="col" class="text-end">{{ __('Harga') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <span class="fw-semibold">{{ $package->name }}</span>
                                    <div class="text-secondary small">
                                        {{ __('Aktif :days hari', ['days' => $package->active_days]) }}
                                    </div>
                                </td>
                                <td class="text-end">Rp {{ number_format($packagePrice, 0, ',', '.') }}</td>
                            </tr>

                            @if ($template)
                                <tr>
                                    <td>
                                        <span class="fw-semibold">{{ $template->name }}</span>
                                        <div class="text-secondary small">
                                            {{ $template->category->name }}
                                            @unless ($templatePrice > 0)
                                                · {{ __('Termasuk dalam paket') }}
                                            @endunless
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        @if ($templatePrice > 0)
                                            Rp {{ number_format($templatePrice, 0, ',', '.') }}
                                        @else
                                            <span class="text-secondary">Rp 0</span>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                        <tfoot>
                            <tr class="border-top">
                                <th scope="row" class="text-end">{{ __('Total') }}</th>
                                <td class="text-end fw-semibold">Rp {{ number_format($total, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <p class="text-secondary">
                        {{ __('Pesanan berlaku 24 jam. Lewat batas itu pesanan kedaluwarsa dan Anda perlu memesan ulang.') }}
                    </p>

                    <form method="POST" action="{{ route('checkout.store') }}">
                        @csrf
                        <input type="hidden" name="package" value="{{ $package->slug }}">
                        <input type="hidden" name="template" value="{{ $template?->slug }}">

                        @error('package')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
                        @error('template')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror

                        <button type="submit" class="btn btn-primary w-100">
                            {{ __('Buat pesanan') }}
                        </button>
                    </form>

                    <a href="{{ route('pricing.index') }}" class="btn btn-link w-100 mt-2">
                        {{ __('Ganti paket') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
