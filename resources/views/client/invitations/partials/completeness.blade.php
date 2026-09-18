{{--
    Completeness indicator (16.5) — the publish gate of docs/04 § 4, rendered.

    Each unmet requirement links to the tab that fixes it, because a checklist
    that only tells the client what is wrong is half a feature.
--}}
@php
    $total = count($requirements);
    $done = count(array_filter($requirements, fn ($requirement) => $requirement->met));
    $percent = $total === 0 ? 0 : (int) round($done / $total * 100);
@endphp

<div class="card">
    <div class="card-header">
        <h3 class="card-title mb-0">{{ __('Siap terbit') }}</h3>
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-between small mb-1">
            <span class="text-secondary">{{ __(':done dari :total', ['done' => $done, 'total' => $total]) }}</span>
            <span class="fw-semibold">{{ $percent }}%</span>
        </div>

        <div class="progress mb-3" role="progressbar" aria-label="{{ __('Kelengkapan undangan') }}"
             aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100" style="height: .5rem;">
            <div class="progress-bar {{ $isReady ? 'bg-success' : '' }}" style="width: {{ $percent }}%"></div>
        </div>

        <ul class="list-unstyled mb-0 small">
            @foreach ($requirements as $requirement)
                <li class="d-flex gap-2 mb-2">
                    @if ($requirement->met)
                        <i class="bi bi-check-circle-fill text-success mt-1"></i>
                    @else
                        <i class="bi bi-circle text-secondary mt-1"></i>
                    @endif
                    <div>
                        <div class="{{ $requirement->met ? 'text-secondary' : 'fw-semibold' }}">
                            {{ $requirement->label }}
                        </div>
                        @if (! $requirement->met)
                            <div class="text-secondary">{{ $requirement->hint }}</div>
                            @if ($requirement->tab !== null)
                                <a href="{{ route('client.invitations.edit', [$invitation, 'tab' => $requirement->tab]) }}">
                                    {{ __('Lengkapi') }}
                                </a>
                            @endif
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>

        {{--
            The publish control (23.2). Disabled until every check passes —
            and refused by the action too, because a builder tab left open
            while a photo is deleted elsewhere is not evidence of anything.
        --}}
        @if ($canEdit)
            <hr>

            @if ($invitation->status === App\Enums\InvitationStatus::Published)
                <p class="small text-secondary">
                    {{ __('Terbit sejak :date.', ['date' => $invitation->published_at?->timezone($invitation->timezone)->translatedFormat('d M Y')]) }}
                    @if ($invitation->expires_at)
                        {{ __('Aktif sampai :date.', ['date' => $invitation->expires_at->timezone($invitation->timezone)->translatedFormat('d M Y')]) }}
                    @endif
                </p>

                <a href="{{ route('invitation.show', ['slug' => $invitation->slug]) }}" target="_blank" rel="noopener"
                   class="btn btn-sm btn-outline-primary w-100 mb-2">
                    <i class="bi bi-box-arrow-up-right me-1"></i>{{ __('Buka undangan') }}
                </a>

                <form method="POST" action="{{ route('client.invitations.unpublish', $invitation) }}"
                      onsubmit="return confirm('{{ __('Tamu tidak akan bisa membuka undangan ini. Lanjutkan?') }}')">
                    @csrf
                    @method('delete')
                    <button type="submit" class="btn btn-sm btn-outline-secondary w-100">
                        {{ __('Jadikan draf') }}
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('client.invitations.publish', $invitation) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary w-100" @disabled(! $isReady)>
                        <i class="bi bi-send me-1"></i>{{ __('Terbitkan undangan') }}
                    </button>
                </form>

                @unless ($isReady)
                    <p class="form-text mb-0">{{ __('Lengkapi daftar di atas untuk menerbitkan.') }}</p>
                @endunless
            @endif
        @endif
    </div>
</div>
