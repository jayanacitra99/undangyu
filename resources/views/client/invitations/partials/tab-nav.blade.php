{{--
    Builder tab navigation (16.2). Pills on desktop, a select on phones.

    Both are plain links to the same route with a different ?tab= — no client
    state to lose, and a half-written tab cannot be reached by a stale URL.
--}}
<div class="card mb-3">
    <div class="card-body">
        <label class="form-label d-lg-none" for="builder-tab">{{ __('Bagian') }}</label>
        <select id="builder-tab" class="form-select d-lg-none" data-builder-tabs>
            @foreach ($tabs as $key => $meta)
                <option value="{{ route('client.invitations.edit', [$invitation, 'tab' => $key]) }}"
                        @selected($key === $tab)>
                    {{ __($meta['label']) }}
                </option>
            @endforeach
        </select>

        <nav class="nav nav-pills flex-column d-none d-lg-flex gap-1" aria-label="{{ __('Bagian undangan') }}">
            @foreach ($tabs as $key => $meta)
                <a class="nav-link {{ $key === $tab ? 'active' : 'link-body-emphasis' }}"
                   href="{{ route('client.invitations.edit', [$invitation, 'tab' => $key]) }}"
                   @if ($key === $tab) aria-current="page" @endif>
                    <i class="bi {{ $meta['icon'] }} me-2"></i>{{ __($meta['label']) }}
                </a>
            @endforeach
        </nav>
    </div>
</div>

@push('scripts')
    <script>
        document.querySelectorAll('[data-builder-tabs]').forEach((select) => {
            select.addEventListener('change', () => {
                window.location.href = select.value;
            });
        });
    </script>
@endpush
