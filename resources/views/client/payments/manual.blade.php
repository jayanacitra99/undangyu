@extends('layouts.client')

@section('title', __('Transfer Bank Manual'))

@section('content')
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">{{ __('Tujuan transfer') }}</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 fw-normal text-secondary">{{ __('Bank') }}</dt>
                        <dd class="col-sm-8 fw-semibold">{{ $bank['name'] }}</dd>

                        <dt class="col-sm-4 fw-normal text-secondary">{{ __('Nomor rekening') }}</dt>
                        <dd class="col-sm-8 fw-semibold font-monospace">{{ $bank['account_number'] }}</dd>

                        <dt class="col-sm-4 fw-normal text-secondary">{{ __('Atas nama') }}</dt>
                        <dd class="col-sm-8">{{ $bank['account_name'] }}</dd>

                        <dt class="col-sm-4 fw-normal text-secondary">{{ __('Nominal') }}</dt>
                        <dd class="col-sm-8 fw-semibold">
                            Rp {{ number_format((float) $order->total, 0, ',', '.') }}
                        </dd>
                    </dl>

                    @if ($bank['instructions'])
                        <p class="text-secondary small mt-3 mb-0">{{ $bank['instructions'] }}</p>
                    @endif
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">{{ __('Unggah bukti transfer') }}</div>
                <div class="card-body">
                    @if ($payment?->verification_note && $payment->status->value === 'failed')
                        <div class="alert alert-warning">
                            <strong>{{ __('Bukti sebelumnya ditolak:') }}</strong>
                            {{ $payment->verification_note }}
                        </div>
                    @elseif ($payment?->proof_path)
                        <div class="alert alert-info">
                            {{ __('Bukti transfer sudah kami terima dan sedang diverifikasi. Mengunggah ulang akan menggantikan bukti sebelumnya.') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('client.orders.manual.store', $order) }}"
                          enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label for="proof" class="form-label">{{ __('Berkas bukti') }}</label>
                            <input type="file" id="proof" name="proof" required
                                   accept="image/jpeg,image/png,image/webp,application/pdf"
                                   class="form-control @error('proof') is-invalid @enderror">
                            @error('proof')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">{{ __('JPG, PNG, WEBP atau PDF. Maksimal 5 MB.') }}</div>
                        </div>

                        <button type="submit" class="btn btn-primary">{{ __('Kirim bukti transfer') }}</button>
                        <a href="{{ route('client.orders.show', $order) }}" class="btn btn-link">
                            {{ __('Kembali ke pesanan') }}
                        </a>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">{{ __('Pesanan') }}</div>
                <div class="card-body">
                    <dl class="mb-0">
                        <dt class="text-secondary fw-normal">{{ __('Nomor pesanan') }}</dt>
                        <dd class="fw-semibold">{{ $order->order_number }}</dd>

                        <dt class="text-secondary fw-normal">{{ __('Batas pembayaran') }}</dt>
                        <dd>{{ $order->payment_deadline?->timezone(config('app.display_timezone'))->format('d M Y H:i') ?? '—' }}</dd>

                        <dt class="text-secondary fw-normal">{{ __('Status') }}</dt>
                        <dd>
                            <span class="badge {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection
