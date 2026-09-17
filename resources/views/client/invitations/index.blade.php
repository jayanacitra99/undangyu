{{--
    The client's invitations (16.1).

    Guest and RSVP counts belong in this table and are not here yet: guests
    land in Session 20. The columns arrive with the data, not before it.
--}}
@extends('layouts.client')

@section('title', __('Undangan'))

@section('content')
    <div class="card">
        <div class="card-body">
            @if ($invitations->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-envelope-heart display-4 text-secondary d-block mb-3"></i>
                    <p class="text-secondary mb-3">{{ __('Belum ada undangan.') }}</p>
                    <a href="{{ route('pricing.index') }}" class="btn btn-primary">
                        {{ __('Pilih paket') }}
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('Undangan') }}</th>
                                <th scope="col">{{ __('Acara') }}</th>
                                <th scope="col" class="text-end">{{ __('Dilihat') }}</th>
                                <th scope="col">{{ __('Status') }}</th>
                                <th scope="col" class="text-end">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invitations as $invitation)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $invitation->title }}</div>
                                        <div class="text-secondary small">
                                            <span class="font-monospace">/{{ $invitation->slug }}</span>
                                            · {{ $invitation->package?->name }}
                                        </div>
                                    </td>
                                    <td>
                                        @if ($invitation->firstEvent !== null)
                                            {{ $invitation->firstEvent->local_start_at->translatedFormat('d M Y H:i') }}
                                            <div class="text-secondary small">{{ $invitation->eventType?->name }}</div>
                                        @else
                                            <span class="text-secondary">{{ __('Belum diatur') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format($invitation->view_count, 0, ',', '.') }}</td>
                                    <td>
                                        <span class="badge {{ $invitation->status->badgeClass() }}">
                                            {{ $invitation->status->label() }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('client.invitations.edit', $invitation) }}"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil-square me-1"></i>{{ __('Kelola') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $invitations->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
