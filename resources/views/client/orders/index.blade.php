@php use App\Support\Money; @endphp
@extends('layouts.client')

@section('title', __('Pesanan'))

@section('content')
    <div class="card">
        <div class="card-body">
            @if ($orders->isEmpty())
                <p class="text-secondary mb-3">{{ __('Belum ada pesanan.') }}</p>
                <a href="{{ route('pricing.index') }}" class="btn btn-primary">{{ __('Lihat paket') }}</a>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('Nomor') }}</th>
                                <th scope="col">{{ __('Paket') }}</th>
                                <th scope="col">{{ __('Total') }}</th>
                                <th scope="col">{{ __('Batas bayar') }}</th>
                                <th scope="col">{{ __('Status') }}</th>
                                <th scope="col" class="text-end">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $order)
                                <tr>
                                    <td class="fw-semibold">{{ $order->order_number }}</td>
                                    <td>{{ $order->package->name }}</td>
                                    <td>{{ Money::idr($order->total) }}</td>
                                    <td>
                                        {{ $order->payment_deadline?->timezone(config('app.timezone'))->format('d M Y H:i') ?? '—' }}
                                    </td>
                                    <td>
                                        <span class="badge {{ $order->status->badgeClass() }}">
                                            {{ $order->status->label() }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('client.orders.show', $order) }}"
                                           class="btn btn-sm btn-outline-primary">{{ __('Detail') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
