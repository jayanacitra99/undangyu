@extends('layouts.admin')

@section('title', __('Verifikasi Pembayaran'))

@section('content')
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            @if ($payments->isEmpty())
                <p class="text-secondary mb-0">{{ __('Tidak ada transfer manual yang menunggu verifikasi.') }}</p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('Pesanan') }}</th>
                                <th scope="col">{{ __('Klien') }}</th>
                                <th scope="col">{{ __('Nominal') }}</th>
                                <th scope="col">{{ __('Diunggah') }}</th>
                                <th scope="col">{{ __('Bukti') }}</th>
                                <th scope="col" class="text-end">{{ __('Keputusan') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($payments as $payment)
                                <tr>
                                    <td>
                                        <span class="fw-semibold">{{ $payment->order->order_number }}</span>
                                        <div class="text-secondary small">{{ $payment->order->package->name }}</div>
                                    </td>
                                    <td>
                                        {{ $payment->order->user->name }}
                                        <div class="text-secondary small">{{ $payment->order->user->email }}</div>
                                    </td>
                                    <td>
                                        Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}
                                        @if ($payment->matchesOrderTotal())
                                            <span class="badge text-bg-success">{{ __('Cocok') }}</span>
                                        @else
                                            <div class="text-danger small">
                                                {{ __('Tagihan Rp :total', ['total' => number_format((float) $payment->order->total, 0, ',', '.')]) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $payment->updated_at->timezone(config('app.display_timezone'))->format('d M Y H:i') }}</td>
                                    <td>
                                        <a href="{{ route('admin.payments.proof', $payment) }}" target="_blank"
                                           rel="noopener" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-file-earmark-image"></i> {{ __('Lihat') }}
                                        </a>
                                    </td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('admin.payments.approve', $payment) }}"
                                              class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success"
                                                    onclick="return confirm('{{ __('Setujui pembayaran ini? Undangan akan dibuat.') }}')">
                                                {{ __('Setujui') }}
                                            </button>
                                        </form>

                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#reject-{{ $payment->id }}">
                                            {{ __('Tolak') }}
                                        </button>
                                    </td>
                                </tr>
                                <tr class="collapse" id="reject-{{ $payment->id }}">
                                    <td colspan="6" class="bg-body-tertiary">
                                        <form method="POST" action="{{ route('admin.payments.reject', $payment) }}"
                                              class="row g-2 align-items-end">
                                            @csrf
                                            <div class="col-md-9">
                                                <label for="note-{{ $payment->id }}" class="form-label">
                                                    {{ __('Alasan penolakan (dibaca klien)') }}
                                                </label>
                                                <input type="text" id="note-{{ $payment->id }}" name="note"
                                                       maxlength="500" required class="form-control"
                                                       placeholder="{{ __('Nominal tidak sesuai tagihan.') }}">
                                            </div>
                                            <div class="col-md-3">
                                                <button type="submit" class="btn btn-danger w-100">
                                                    {{ __('Tolak pembayaran') }}
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">{{ $payments->links() }}</div>
            @endif
        </div>
    </div>
@endsection
