{{--
    "Tema" tab (19.4).

    The controls are generated from the template's own `config_schema` — this
    file knows about control *types*, never about a particular template's keys.
    Whatever it renders, ThemeConfigValidator checks again on the way in.
--}}
@if (empty($themeSchema))
    <p class="text-secondary mb-0">{{ __('Tema ini belum punya pengaturan yang bisa diubah.') }}</p>
@else
    <form method="POST" action="{{ route('client.invitations.theme.update', $invitation) }}">
        @csrf
        @method('patch')

        <fieldset @disabled(! $canEdit)>
            @foreach ($themeSchema as $group => $fields)
                <h5 class="mb-3">{{ __(Str::headline($group)) }}</h5>

                <div class="row g-3 mb-4">
                    @foreach ($fields as $key => $field)
                        @php
                            $name = "theme_config[{$group}][{$key}]";
                            $id = "theme-{$group}-{$key}";
                            $current = old("theme_config.{$group}.{$key}", $themeConfig[$group][$key] ?? ($field['default'] ?? null));
                            $label = $field['label'] ?? Str::headline($key);
                            $error = $errors->first("theme_config.{$group}.{$key}");
                        @endphp

                        <div class="col-md-4">
                            @if (($field['type'] ?? null) === 'boolean')
                                <div class="form-check form-switch">
                                    {{-- An unchecked switch posts nothing, so the hidden input carries the "off". --}}
                                    <input type="hidden" name="{{ $name }}" value="0">
                                    <input
                                        id="{{ $id }}"
                                        class="form-check-input"
                                        type="checkbox"
                                        role="switch"
                                        name="{{ $name }}"
                                        value="1"
                                        @checked((bool) $current)
                                    >
                                    <label class="form-check-label" for="{{ $id }}">{{ $label }}</label>
                                </div>
                            @else
                                <label class="form-label" for="{{ $id }}">{{ $label }}</label>

                                @if (($field['type'] ?? null) === 'color')
                                    <input
                                        id="{{ $id }}"
                                        type="color"
                                        name="{{ $name }}"
                                        class="form-control form-control-color @error("theme_config.{$group}.{$key}") is-invalid @enderror"
                                        value="{{ $current }}"
                                    >
                                @elseif (($field['type'] ?? null) === 'select')
                                    <select
                                        id="{{ $id }}"
                                        name="{{ $name }}"
                                        class="form-select @error("theme_config.{$group}.{$key}") is-invalid @enderror"
                                    >
                                        @foreach ($field['options'] ?? [] as $option)
                                            <option value="{{ $option }}" @selected($current === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                @elseif (($field['type'] ?? null) === 'number')
                                    <input
                                        id="{{ $id }}"
                                        type="number"
                                        name="{{ $name }}"
                                        class="form-control @error("theme_config.{$group}.{$key}") is-invalid @enderror"
                                        value="{{ $current }}"
                                        @isset($field['min']) min="{{ $field['min'] }}" @endisset
                                        @isset($field['max']) max="{{ $field['max'] }}" @endisset
                                    >
                                @else
                                    <input
                                        id="{{ $id }}"
                                        type="text"
                                        name="{{ $name }}"
                                        maxlength="200"
                                        class="form-control @error("theme_config.{$group}.{$key}") is-invalid @enderror"
                                        value="{{ $current }}"
                                    >
                                @endif
                            @endif

                            @if ($error)
                                <div class="invalid-feedback d-block">{{ $error }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endforeach

            <button type="submit" class="btn btn-primary">{{ __('Simpan tema') }}</button>
        </fieldset>
    </form>
@endif
