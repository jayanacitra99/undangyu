{{--
    RSVP dashboard (28.4).

    Four counters, two breakdowns and the list. Server-rendered: a client opens
    this every hour in the last week before the wedding, and a page that is
    already there beats a bundle that fetches one.
--}}
@extends('layouts.client')

@section('title', __('RSVP') . ' — ' . $invitation->title)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('client.invitations.index') }}">{{ __('Undangan') }}</a></li>
    <li class="breadcrumb-item">
        <a href="{{ route('client.invitations.edit', $invitation) }}">{{ $invitation->title }}</a>
    </li>
    <li class="breadcrumb-item active">{{ __('RSVP') }}</li>
@endsection

@section('content')
    <div class="row g-3 mb-3">
        @php
            $cards = [
                ['label' => __('Hadir'), 'value' => $totals['yes'], 'hint' => __(':pax orang', ['pax' => $totals['pax']]), 'class' => 'text-bg-success'],
                ['label' => __('Tidak hadir'), 'value' => $totals['no'], 'hint' => '', 'class' => 'text-bg-secondary'],
                ['label' => __('Masih ragu'), 'value' => $totals['maybe'], 'hint' => __(':pax orang', ['pax' => $totals['maybe_pax']]), 'class' => 'text-bg-warning'],
                ['label' => __('Sudah menjawab'), 'value' => $totals['guests_responded'], 'hint' => __('dari :total tamu', ['total' => $totals['guests_total']]), 'class' => 'text-bg-primary'],
            ];
        @endphp

        @foreach ($cards as $card)
            <div class="col-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <span class="badge {{ $card['class'] }} mb-2">{{ $card['label'] }}</span>
                        <div class="fs-3 fw-semibold lh-1">{{ number_format($card['value'], 0, ',', '.') }}</div>
                        @if ($card['hint'])
                            <div class="text-secondary small">{{ $card['hint'] }}</div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h3 class="card-title mb-0">{{ __('Per acara') }}</h3></div>
                <div class="card-body">
                    @forelse ($sessions as $session)
                        <div class="d-flex justify-content-between border-bottom py-2 small">
                            <span>{{ $session['name'] }}</span>
                            <span class="text-secondary">
                                {{ __(':yes hadir · :pax orang', ['yes' => $session['yes'], 'pax' => $session['pax']]) }}
                            </span>
                        </div>
                    @empty
                        <p class="text-secondary small mb-0">{{ __('Belum ada jawaban.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h3 class="card-title mb-0">{{ __('Per grup tamu') }}</h3></div>
                <div class="card-body">
                    @forelse ($groups as $group)
                        <div class="d-flex justify-content-between border-bottom py-2 small">
                            <span>
                                <span class="d-inline-block rounded-circle me-1 align-middle"
                                      style="width: .6rem; height: .6rem; background-color: {{ $group['color'] ?? '#adb5bd' }}"></span>
                                {{ $group['name'] }}
                            </span>
                            <span class="text-secondary">
                                {{ __(':yes hadir · :pax orang', ['yes' => $group['yes'], 'pax' => $group['pax']]) }}
                            </span>
                        </div>
                    @empty
                        <p class="text-secondary small mb-0">{{ __('Belum ada jawaban dari tamu terdaftar.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap gap-2 align-items-center">
            <h3 class="card-title mb-0">{{ __('Daftar jawaban') }}</h3>

            <form method="GET" class="ms-auto d-flex flex-wrap gap-2">
                <input type="search" name="search" value="{{ $filters['search'] }}"
                       class="form-control form-control-sm" style="max-width: 14rem"
                       placeholder="{{ __('Cari nama, telepon, pesan') }}" aria-label="{{ __('Cari jawaban') }}">

                <select name="attendance" class="form-select form-select-sm" style="max-width: 10rem"
                        aria-label="{{ __('Saring kehadiran') }}">
                    <option value="">{{ __('Semua jawaban') }}</option>
                    @foreach ($attendances as $attendance)
                        <option value="{{ $attendance->value }}" @selected($filters['attendance'] === $attendance->value)>
                            {{ $attendance->label() }}
                        </option>
                    @endforeach
                </select>

                <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('Cari') }}</button>
                <a href="{{ route('client.invitations.rsvps.export', $invitation) }}"
                   class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>{{ __('Ekspor') }}
                </a>
            </form>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('Nama') }}</th>
                            <th scope="col">{{ __('Kehadiran') }}</th>
                            <th scope="col" class="text-center">{{ __('Orang') }}</th>
                            <th scope="col">{{ __('Acara') }}</th>
                            <th scope="col">{{ __('Pesan') }}</th>
                            <th scope="col">{{ __('Waktu') }}</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rsvps as $rsvp)
                            <tr>
                                <td>
                                    {{ $rsvp->name }}
                                    @if ($rsvp->guest?->group)
                                        <span class="badge" style="background-color: {{ $rsvp->guest->group->color ?? '#6c757d' }}">
                                            {{ $rsvp->guest->group->name }}
                                        </span>
                                    @endif
                                    @if ($rsvp->guest_id === null)
                                        <span class="badge text-bg-light">{{ __('Tanpa token') }}</span>
                                    @endif
                                    <div class="text-secondary small">{{ $rsvp->phone }}</div>
                                </td>
                                <td>
                                    <span class="badge {{ $rsvp->attendance->badgeClass() }}">
                                        {{ $rsvp->attendance->label() }}
                                    </span>
                                </td>
                                <td class="text-center">{{ $rsvp->pax }}</td>
                                <td class="small">{{ $rsvp->event?->name ?? __('Semua acara') }}</td>
                                <td class="small">{{ $rsvp->notes }}</td>
                                <td class="small text-secondary">
                                    {{ $rsvp->responded_at->timezone($invitation->timezone)->translatedFormat('d M Y H:i') }}
                                </td>
                                <td class="text-end">
                                    <form method="POST"
                                          action="{{ route('client.invitations.rsvps.destroy', [$invitation, $rsvp]) }}"
                                          onsubmit="return confirm('{{ __('Hapus jawaban ini?') }}')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                aria-label="{{ __('Hapus jawaban :name', ['name' => $rsvp->name]) }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-secondary py-4">
                                    {{ __('Belum ada konfirmasi kehadiran.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $rsvps->links() }}</div>
        </div>
    </div>
@endsection
