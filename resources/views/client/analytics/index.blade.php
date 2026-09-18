{{--
    Analytics (M9.3, 30.5).

    Every figure here comes from `invitation_stats_daily` by way of
    InvitationAnalytics — the raw views table is never queried from a page. The
    one exception is the device split, which has nowhere else to live and says
    so in the service.

    The chart is an inline SVG rather than a charting library: it is one line
    over thirty points, and shipping a bundle for that to a dashboard that
    already loads AdminLTE is a trade nobody wins.
--}}
@extends('layouts.client')

@section('title', __('Statistik') . ' — ' . $invitation->title)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('client.invitations.index') }}">{{ __('Undangan') }}</a></li>
    <li class="breadcrumb-item">
        <a href="{{ route('client.invitations.edit', $invitation) }}">{{ $invitation->title }}</a>
    </li>
    <li class="breadcrumb-item active">{{ __('Statistik') }}</li>
@endsection

@section('content')
    @php
        $peak = max(1, collect($series)->max('views'));
        $width = 640;
        $height = 160;
        $step = count($series) > 1 ? $width / (count($series) - 1) : $width;

        $points = collect($series)
            ->map(fn (array $day, int $index): string => round($index * $step, 1)
                . ',' . round($height - ($day['views'] / $peak * ($height - 12)), 1))
            ->implode(' ');
    @endphp

    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="btn-group btn-group-sm" role="group" aria-label="{{ __('Rentang waktu') }}">
            @foreach ($windows as $window)
                <a href="{{ route('client.invitations.analytics.index', [$invitation, 'days' => $window]) }}"
                   class="btn {{ $days === $window ? 'btn-primary' : 'btn-outline-secondary' }}">
                    {{ __(':days hari', ['days' => $window]) }}
                </a>
            @endforeach
        </div>

        <span class="text-secondary small ms-auto">
            @if ($lastRolledUpAt)
                {{ __('Data sampai :date.', ['date' => $lastRolledUpAt->translatedFormat('d M Y')]) }}
            @else
                {{ __('Belum ada data harian. Statistik dihitung setiap dini hari.') }}
            @endif
        </span>
    </div>

    <div class="row g-3 mb-3">
        @php
            $cards = [
                ['label' => __('Dilihat'), 'value' => $totals['views']],
                ['label' => __('Pengunjung unik'), 'value' => $totals['unique_visitors']],
                ['label' => __('Dibuka tamu undangan'), 'value' => $totals['guest_opens']],
                ['label' => __('Ucapan'), 'value' => $totals['wishes_count']],
            ];
        @endphp

        @foreach ($cards as $card)
            <div class="col-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-secondary small">{{ $card['label'] }}</div>
                        <div class="fs-3 fw-semibold lh-1">{{ number_format($card['value'], 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title mb-0">{{ __('Kunjungan per hari') }}</h3>
        </div>
        <div class="card-body">
            @if ($totals['views'] === 0)
                <p class="text-secondary small mb-0">
                    {{ __('Belum ada kunjungan yang tercatat pada rentang ini.') }}
                </p>
            @else
                <svg viewBox="0 0 {{ $width }} {{ $height }}" class="w-100" style="height: 10rem;"
                     role="img" aria-label="{{ __('Grafik kunjungan harian') }}" preserveAspectRatio="none">
                    <polyline points="{{ $points }}" fill="none" stroke="currentColor" stroke-width="2"
                              vector-effect="non-scaling-stroke" class="text-primary"></polyline>
                </svg>

                <div class="d-flex justify-content-between text-secondary small mt-1">
                    <span>{{ \Illuminate\Support\Carbon::parse($series[0]['date'])->translatedFormat('d M') }}</span>
                    <span>{{ __('Puncak :peak/hari', ['peak' => $peak]) }}</span>
                    <span>{{ \Illuminate\Support\Carbon::parse(end($series)['date'])->translatedFormat('d M') }}</span>
                </div>
            @endif
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h3 class="card-title mb-0">{{ __('Corong RSVP') }}</h3></div>
                <div class="card-body">
                    @php
                        $steps = [
                            ['label' => __('Tamu terdaftar'), 'value' => $funnel['guests']],
                            ['label' => __('Membuka undangan'), 'value' => $funnel['opened']],
                            ['label' => __('Mengisi RSVP'), 'value' => $funnel['responded']],
                            ['label' => __('Menyatakan hadir'), 'value' => $funnel['attending']],
                        ];
                        $top = max(1, $funnel['guests']);
                    @endphp

                    @foreach ($steps as $step)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small">
                                <span>{{ $step['label'] }}</span>
                                <span class="fw-semibold">{{ number_format($step['value'], 0, ',', '.') }}</span>
                            </div>
                            <div class="progress" style="height: .4rem;" role="progressbar"
                                 aria-label="{{ $step['label'] }}" aria-valuenow="{{ $step['value'] }}"
                                 aria-valuemin="0" aria-valuemax="{{ $top }}">
                                <div class="progress-bar" style="width: {{ round($step['value'] / $top * 100, 1) }}%"></div>
                            </div>
                        </div>
                    @endforeach

                    <p class="text-secondary small mb-0">
                        {{ __('Total :pax orang dikonfirmasi hadir.', ['pax' => $funnel['pax']]) }}
                    </p>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h3 class="card-title mb-0">{{ __('Perangkat') }}</h3></div>
                <div class="card-body">
                    @forelse ($devices as $device)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small">
                                <span>{{ $device['label'] }}</span>
                                <span class="text-secondary">
                                    {{ number_format($device['views'], 0, ',', '.') }} · {{ $device['share'] }}%
                                </span>
                            </div>
                            <div class="progress" style="height: .4rem;" role="progressbar"
                                 aria-label="{{ $device['label'] }}" aria-valuenow="{{ $device['share'] }}"
                                 aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar bg-secondary" style="width: {{ $device['share'] }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-secondary small mb-0">{{ __('Belum ada kunjungan pada rentang ini.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
