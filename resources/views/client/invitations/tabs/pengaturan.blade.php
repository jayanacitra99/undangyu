{{--
    "Pengaturan" tab (19.5) plus the section manager (19.3).

    Both answer "how does this invitation behave": which blocks a guest sees
    and in what order, and which features are switched on.

    A toggle the package never sold is disabled here and refused by the
    FormRequest — the disabled attribute is the courtesy, the server is the rule.
--}}
<h5 class="mb-3">{{ __('Urutan & tampilan bagian') }}</h5>

<div
    data-island="sections-manager"
    data-sections="{{ json_encode($sections) }}"
    data-store-url="{{ route('api.sections.store', $invitation) }}"
    data-reorder-url="{{ route('api.sections.reorder', $invitation) }}"
    data-item-url-template="{{ route('api.sections.update', ['section' => '__ID__']) }}"
    data-csrf-token="{{ csrf_token() }}"
    data-can-edit="{{ $canEdit ? '1' : '0' }}"
></div>

<hr class="my-4">

<form method="POST" action="{{ route('client.invitations.settings.update', $invitation) }}">
    @csrf
    @method('patch')

    <fieldset @disabled(! $canEdit)>
        <h5 class="mb-3">{{ __('Fitur') }}</h5>

        <div class="row g-3 mb-4">
            @foreach (['rsvp_enabled' => __('RSVP'), 'guestbook_enabled' => __('Buku tamu'), 'music_enabled' => __('Musik latar'), 'music_autoplay' => __('Putar musik otomatis'), 'countdown_enabled' => __('Hitung mundur'), 'gift_enabled' => __('Amplop digital')] as $key => $label)
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input type="hidden" name="{{ $key }}" value="0">
                        <input
                            id="setting-{{ $key }}"
                            class="form-check-input @error($key) is-invalid @enderror"
                            type="checkbox"
                            role="switch"
                            name="{{ $key }}"
                            value="1"
                            @checked((bool) old($key, $settings[$key]))
                            @disabled(! $settingsAvailability[$key])
                        >
                        <label class="form-check-label" for="setting-{{ $key }}">{{ $label }}</label>
                    </div>

                    @unless ($settingsAvailability[$key])
                        <div class="form-text">{{ __('Tidak termasuk dalam paket ini.') }}</div>
                    @endunless

                    @error($key)
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            @endforeach
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label" for="guestbook_moderation">{{ __('Moderasi buku tamu') }}</label>
                <select id="guestbook_moderation" name="guestbook_moderation" class="form-select">
                    @foreach (App\Support\InvitationSettings::MODERATION_MODES as $value => $label)
                        <option value="{{ $value }}" @selected(old('guestbook_moderation', $settings['guestbook_moderation']) === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <h5 class="mb-3">{{ __('Akses') }}</h5>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label" for="visibility">{{ __('Siapa yang bisa membuka') }}</label>
                <select id="visibility" name="visibility" class="form-select @error('visibility') is-invalid @enderror">
                    @foreach (App\Enums\InvitationVisibility::cases() as $case)
                        <option value="{{ $case->value }}" @selected(old('visibility', $invitation->visibility->value) === $case->value)>
                            {{ $case->label() }}
                        </option>
                    @endforeach
                </select>
                @error('visibility')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label" for="password">{{ __('Kata sandi undangan') }}</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    autocomplete="new-password"
                    class="form-control @error('password') is-invalid @enderror"
                    placeholder="{{ $invitation->password === null ? __('Belum diatur') : __('Biarkan kosong untuk tetap sama') }}"
                >
                <div class="form-text">{{ __('Dipakai hanya jika akses diatur "Dengan kata sandi".') }}</div>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <button type="submit" class="btn btn-primary">{{ __('Simpan pengaturan') }}</button>
    </fieldset>
</form>

@push('scripts')
    @vite('resources/js/islands/sections-manager.js')
@endpush
