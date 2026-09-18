{{--
    Guestbook moderation (29.4).

    Everything a client does here is the same bulk endpoint over a selection,
    including the single-row buttons — one path, one place the feed cache is
    cleared.

    Every message is printed with {{ }}, which escapes. A wish containing
    <script> renders as the text the guest typed.
--}}
@extends('layouts.client')

@section('title', __('Ucapan') . ' — ' . $invitation->title)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('client.invitations.index') }}">{{ __('Undangan') }}</a></li>
    <li class="breadcrumb-item">
        <a href="{{ route('client.invitations.edit', $invitation) }}">{{ $invitation->title }}</a>
    </li>
    <li class="breadcrumb-item active">{{ __('Ucapan') }}</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex flex-wrap gap-2 align-items-center">
            <h3 class="card-title mb-0">
                {{ __('Buku tamu') }}
                <span class="badge text-bg-warning ms-1">{{ $counts['pending'] }} {{ __('menunggu') }}</span>
                <span class="badge text-bg-success">{{ $counts['approved'] }} {{ __('tampil') }}</span>
                <span class="badge text-bg-secondary">{{ $counts['rejected'] }} {{ __('ditolak') }}</span>
            </h3>

            <form method="GET" class="ms-auto d-flex flex-wrap gap-2">
                <input type="search" name="search" value="{{ $filters['search'] }}"
                       class="form-control form-control-sm" style="max-width: 14rem"
                       placeholder="{{ __('Cari nama atau ucapan') }}" aria-label="{{ __('Cari ucapan') }}">

                <select name="status" class="form-select form-select-sm" style="max-width: 10rem"
                        aria-label="{{ __('Saring status') }}">
                    <option value="">{{ __('Semua status') }}</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>

                <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('Cari') }}</button>
                <a href="{{ route('client.invitations.wishes.export', $invitation) }}"
                   class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>{{ __('Ekspor') }}
                </a>
            </form>
        </div>

        <form method="POST" action="{{ route('client.invitations.wishes.moderate', $invitation) }}">
            @csrf

            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                    <div class="form-check">
                        <input id="wishes-all" class="form-check-input" type="checkbox" data-wishes-all>
                        <label class="form-check-label small" for="wishes-all">{{ __('Pilih semua') }}</label>
                    </div>

                    <div class="btn-group btn-group-sm ms-auto" role="group" aria-label="{{ __('Tindakan massal') }}">
                        <button type="submit" name="action" value="approve" class="btn btn-outline-success">
                            {{ __('Tampilkan') }}
                        </button>
                        <button type="submit" name="action" value="reject" class="btn btn-outline-secondary">
                            {{ __('Tolak') }}
                        </button>
                        <button type="submit" name="action" value="pin" class="btn btn-outline-primary">
                            {{ __('Sematkan') }}
                        </button>
                        <button type="submit" name="action" value="unpin" class="btn btn-outline-primary">
                            {{ __('Lepas sematan') }}
                        </button>
                        <button type="submit" name="action" value="delete" class="btn btn-outline-danger"
                                onclick="return confirm('{{ __('Hapus ucapan terpilih?') }}')">
                            {{ __('Hapus') }}
                        </button>
                    </div>
                </div>

                <div class="list-group">
                    @forelse ($wishes as $wish)
                        <label class="list-group-item d-flex gap-3">
                            <input class="form-check-input flex-shrink-0" type="checkbox" name="ids[]"
                                   value="{{ $wish->id }}" data-wish-checkbox
                                   aria-label="{{ __('Pilih ucapan dari :name', ['name' => $wish->name]) }}">

                            <div class="flex-grow-1">
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <span class="fw-semibold">{{ $wish->name }}</span>
                                    <span class="badge {{ $wish->status->badgeClass() }}">{{ $wish->status->label() }}</span>
                                    @if ($wish->is_pinned)
                                        <span class="badge text-bg-primary">
                                            <i class="bi bi-pin-angle me-1"></i>{{ __('Disematkan') }}
                                        </span>
                                    @endif
                                    @if ($wish->guest_id !== null)
                                        <span class="badge text-bg-light">{{ __('Tamu terdaftar') }}</span>
                                    @endif
                                    <span class="text-secondary small ms-auto">
                                        {{ $wish->created_at?->timezone($invitation->timezone)->translatedFormat('d M Y H:i') }}
                                    </span>
                                </div>

                                {{-- Escaped, always. A guest's message is text. --}}
                                <p class="mb-0 mt-1 small">{{ $wish->message }}</p>
                            </div>
                        </label>
                    @empty
                        <p class="text-secondary small mb-0">{{ __('Belum ada ucapan.') }}</p>
                    @endforelse
                </div>

                <div class="mt-3">{{ $wishes->links() }}</div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-wishes-all]').forEach((toggle) => {
            toggle.addEventListener('change', () => {
                document.querySelectorAll('[data-wish-checkbox]').forEach((box) => {
                    box.checked = toggle.checked;
                });
            });
        });
    </script>
@endpush
