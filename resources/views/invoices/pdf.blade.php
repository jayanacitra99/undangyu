{{--
    The invoice document (M2.8), rendered by dompdf.

    Standalone by necessity: dompdf gets no stylesheet from Vite, so the CSS is
    inline and deliberately plain. Everything shown is the order's own snapshot,
    never a live package price.
--}}
@php
    use App\Support\Money;

    $timezone = config('app.display_timezone');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 28mm 18mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1c1917; }
        h1 { font-size: 20px; margin: 0 0 2px; }
        .muted { color: #78716c; }
        .right { text-align: right; }
        table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: top; padding: 0 0 18px; }
        .items th { text-align: left; border-bottom: 1px solid #1c1917; padding: 6px 0; font-size: 10px; text-transform: uppercase; letter-spacing: .04em; }
        .items td { padding: 8px 0; border-bottom: 1px solid #e7e5e4; }
        .totals td { padding: 4px 0; }
        .totals .grand td { border-top: 1px solid #1c1917; font-weight: bold; padding-top: 8px; }
        .badge { display: inline-block; padding: 3px 8px; border: 1px solid #15803d; color: #15803d; font-size: 10px; }
        .footer { margin-top: 28px; font-size: 10px; color: #78716c; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td style="width: 55%">
                <h1>{{ $company['legal_name'] }}</h1>
                @if ($company['address'])
                    <div class="muted">{{ $company['address'] }}</div>
                @endif
                @if ($company['tax_id'])
                    <div class="muted">NPWP {{ $company['tax_id'] }}</div>
                @endif
                @if ($company['email'])
                    <div class="muted">{{ $company['email'] }}</div>
                @endif
            </td>
            <td class="right">
                <div style="font-size: 16px; font-weight: bold;">INVOICE</div>
                <div>{{ $invoice->invoice_number }}</div>
                <div class="muted">
                    {{ __('Tanggal') }}:
                    {{ $invoice->issued_at?->timezone($timezone)->format('d M Y') }}
                </div>
                <div class="muted">
                    {{ __('Jatuh tempo') }}:
                    {{ $invoice->due_at?->timezone($timezone)->format('d M Y') }}
                </div>
                <div style="margin-top: 6px;"><span class="badge">{{ __('LUNAS') }}</span></div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="muted">{{ __('Ditagihkan kepada') }}</div>
                <div style="font-weight: bold;">{{ $order->user->name }}</div>
                <div class="muted">{{ $order->user->email }}</div>
                @if ($order->user->phone)
                    <div class="muted">{{ $order->user->phone }}</div>
                @endif
            </td>
            <td class="right">
                <div class="muted">{{ __('Nomor pesanan') }}</div>
                <div>{{ $order->order_number }}</div>
                <div class="muted" style="margin-top: 6px;">{{ __('Metode pembayaran') }}</div>
                <div>
                    {{ $payment?->method ? str($payment->method)->replace('_', ' ')->title() : __('Transfer bank') }}
                    @if ($payment?->gateway)
                        <span class="muted">({{ $payment->gateway }})</span>
                    @endif
                </div>
                @if ($order->paid_at)
                    <div class="muted" style="margin-top: 6px;">{{ __('Dibayar') }}</div>
                    <div>{{ $order->paid_at->timezone($timezone)->format('d M Y H:i') }}</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                {{-- Widths pinned so the numeric columns cannot run together. --}}
                <th style="width: 49%">{{ __('Deskripsi') }}</th>
                <th class="right" style="width: 11%">{{ __('Jumlah') }}</th>
                <th class="right" style="width: 20%">{{ __('Harga satuan') }}</th>
                <th class="right" style="width: 20%">{{ __('Total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>
                        {{ $item->name }}
                        @if (($item->meta['active_days'] ?? null))
                            <div class="muted">{{ __('Masa aktif :days hari', ['days' => $item->meta['active_days']]) }}</div>
                        @endif
                    </td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">{{ Money::number($item->unit_price) }}</td>
                    <td class="right">{{ Money::number($item->total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals" style="margin-top: 14px;">
        <tr>
            <td class="right" style="width: 78%">{{ __('Subtotal') }}</td>
            <td class="right">{{ Money::idr($order->subtotal) }}</td>
        </tr>
        @if (Money::isPositive($order->discount_amount))
            <tr>
                <td class="right">{{ __('Diskon') }}</td>
                <td class="right">− {{ Money::idr($order->discount_amount) }}</td>
            </tr>
        @endif
        @if (Money::isPositive($order->tax_amount))
            <tr>
                <td class="right">{{ __('Pajak') }}</td>
                <td class="right">{{ Money::idr($order->tax_amount) }}</td>
            </tr>
        @endif
        <tr class="grand">
            <td class="right">{{ __('Total') }}</td>
            <td class="right">{{ Money::idr($order->total) }}</td>
        </tr>
    </table>

    <div class="footer">
        {{ __('Invoice ini sah tanpa tanda tangan dan dibuat otomatis oleh sistem.') }}
        @if ($company['whatsapp'])
            {{ __('Pertanyaan? WhatsApp :number.', ['number' => $company['whatsapp']]) }}
        @endif
    </div>
</body>
</html>
