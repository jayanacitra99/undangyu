{{--
    "Dasar" tab (16.3). Plain Blade, no Vue — it is six fields and a form post.

    The only script here is the slug availability check, which asks the same
    endpoint the islands will use. It is a hint, not a gate: the FormRequest
    checks again on save, so a slug taken in between still loses there.
--}}
<form method="POST" action="{{ route('client.invitations.update', [$invitation, 'tab' => 'dasar']) }}">
    @csrf
    @method('patch')

    <fieldset @disabled(! $canEdit)>
        <div class="mb-3">
            <label for="title" class="form-label">{{ __('Judul undangan') }}</label>
            <input type="text" id="title" name="title" maxlength="150" required
                   class="form-control @error('title') is-invalid @enderror"
                   value="{{ old('title', $invitation->title) }}">
            <div class="form-text">{{ __('Contoh: Pernikahan Rina & Adi') }}</div>
            @error('title')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="slug" class="form-label">{{ __('Alamat undangan') }}</label>
            <div class="input-group has-validation">
                <span class="input-group-text">{{ rtrim(config('app.url'), '/') }}/</span>
                <input type="text" id="slug" name="slug" maxlength="120" required
                       class="form-control font-monospace @error('slug') is-invalid @enderror"
                       value="{{ old('slug', $invitation->slug) }}"
                       data-slug-input
                       data-check-url="{{ route('api.invitations.slug-availability', $invitation) }}"
                       data-current="{{ $invitation->slug }}"
                       aria-describedby="slug-feedback">
                @error('slug')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div id="slug-feedback" class="form-text" data-slug-feedback aria-live="polite">
                {{ __('Huruf kecil, angka dan tanda hubung.') }}
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label for="language" class="form-label">{{ __('Bahasa') }}</label>
                <select id="language" name="language" class="form-select @error('language') is-invalid @enderror">
                    @foreach (App\Models\Invitation::LANGUAGES as $code => $label)
                        <option value="{{ $code }}" @selected(old('language', $invitation->language) === $code)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('language')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label for="timezone" class="form-label">{{ __('Zona waktu') }}</label>
                <select id="timezone" name="timezone" class="form-select @error('timezone') is-invalid @enderror">
                    @foreach (App\Models\Invitation::TIMEZONES as $zone => $label)
                        <option value="{{ $zone }}" @selected(old('timezone', $invitation->timezone) === $zone)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                <div class="form-text">{{ __('Jam acara ditampilkan dalam zona ini.') }}</div>
                @error('timezone')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <hr>

        <p class="text-secondary small">
            {{ __('Yang muncul saat tautan undangan dibagikan di WhatsApp.') }}
        </p>

        <div class="mb-3">
            <label for="meta_title" class="form-label">{{ __('Judul SEO') }}</label>
            <input type="text" id="meta_title" name="meta_title" maxlength="70"
                   class="form-control @error('meta_title') is-invalid @enderror"
                   value="{{ old('meta_title', $invitation->meta_title) }}">
            @error('meta_title')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="meta_description" class="form-label">{{ __('Deskripsi SEO') }}</label>
            <textarea id="meta_description" name="meta_description" rows="2" maxlength="160"
                      class="form-control @error('meta_description') is-invalid @enderror">{{ old('meta_description', $invitation->meta_description) }}</textarea>
            @error('meta_description')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary">{{ __('Simpan') }}</button>
    </fieldset>
</form>

@push('scripts')
    <script>
        (() => {
            const input = document.querySelector('[data-slug-input]');
            const feedback = document.querySelector('[data-slug-feedback]');

            if (!input || !feedback) {
                return;
            }

            const idle = feedback.textContent;
            let timer = null;

            const render = (state, message) => {
                feedback.textContent = message;
                feedback.classList.remove('text-success', 'text-danger');

                if (state !== null) {
                    feedback.classList.add(state ? 'text-success' : 'text-danger');
                }
            };

            input.addEventListener('input', () => {
                window.clearTimeout(timer);

                const slug = input.value.trim();

                if (slug === '' || slug === input.dataset.current) {
                    render(null, idle);
                    return;
                }

                // Same 800ms the dashboard's autosave uses, so the two never
                // feel like different products.
                timer = window.setTimeout(async () => {
                    const url = new URL(input.dataset.checkUrl, window.location.origin);
                    url.searchParams.set('slug', slug);

                    try {
                        const response = await fetch(url, {
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin',
                        });
                        const body = await response.json();

                        render(body.available === true, body.message);
                    } catch {
                        render(null, idle);
                    }
                }, 800);
            });
        })();
    </script>
@endpush
