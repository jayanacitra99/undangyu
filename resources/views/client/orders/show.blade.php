@extends('layouts.client')

@section('title', $order->order_number)

@section('content')
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>{{ __('Rincian pesanan') }}</span>
                    <span class="badge {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span>
                </div>
                <div class="card-body">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('Item') }}</th>
                                <th scope="col" class="text-end">{{ __('Jumlah') }}</th>
                                <th scope="col" class="text-end">{{ __('Harga') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr>
                                    <td>{{ $item->name }}</td>
                                    <td class="text-end">{{ $item->quantity }}</td>
                                    <td class="text-end">Rp {{ number_format((float) $item->total, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th scope="row" colspan="2" class="text-end fw-normal">{{ __('Subtotal') }}</th>
                                <td class="text-end">Rp {{ number_format((float) $order->subtotal, 0, ',', '.') }}</td>
                            </tr>
                            @if ((float) $order->discount_amount > 0)
                                <tr>
                                    <th scope="row" colspan="2" class="text-end fw-normal">{{ __('Diskon') }}</th>
                                    <td class="text-end">− Rp {{ number_format((float) $order->discount_amount, 0, ',', '.') }}</td>
                                </tr>
                            @endif
                            <tr class="border-top">
                                <th scope="row" colspan="2" class="text-end">{{ __('Total') }}</th>
                                <td class="text-end fw-semibold">Rp {{ number_format((float) $order->total, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <dl class="mb-0">
                        <dt class="text-secondary fw-normal">{{ __('Nomor pesanan') }}</dt>
                        <dd class="fw-semibold">{{ $order->order_number }}</dd>

                        <dt class="text-secondary fw-normal">{{ __('Dibuat') }}</dt>
                        <dd>{{ $order->created_at->timezone(config('app.timezone'))->format('d M Y H:i') }}</dd>

                        <dt class="text-secondary fw-normal">{{ __('Batas pembayaran') }}</dt>
                        <dd>{{ $order->payment_deadline?->timezone(config('app.timezone'))->format('d M Y H:i') ?? '—' }}</dd>

                        @if ($order->template)
                            <dt class="text-secondary fw-normal">{{ __('Template') }}</dt>
                            <dd>{{ $order->template->name }}</dd>
                        @endif
                    </dl>
                </div>

                @if ($order->isPayable())
                    <div class="card-footer">
                        @if (session('error'))
                            <div class="alert alert-danger py-2">{{ session('error') }}</div>
                        @endif

                        <form method="POST" action="{{ route('client.orders.pay', $order) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100">
                                {{ __('Bayar sekarang') }}
                            </button>
                        </form>

                        <p class="text-secondary small mb-0 mt-2">
                            {{ __('Anda akan diarahkan ke halaman pembayaran.') }}
                        </p>
                    </div>
                @endif
            </div>

            <a href="{{ route('client.orders.index') }}" class="btn btn-link mt-2">
                {{ __('Kembali ke daftar pesanan') }}
            </a>
        </div>
    </div>
@endsection
