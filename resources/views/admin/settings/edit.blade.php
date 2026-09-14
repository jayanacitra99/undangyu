{{--
    Global settings, one tab per group. Fields are rendered from each row's
    `type` column, so a newly seeded setting shows up with the right input
    without touching this file.
--}}
@extends('layouts.admin')

@section('title', __('Pengaturan'))

@php
    // A rejected save must land on the tab holding the error, or the admin sees
    // a failed submit with nothing to fix on screen. Error keys read
    // "settings.{group}.{key}".
    $erroredGroup = collect($errors->keys())
        ->map(fn (string $key): ?string => explode('.', $key)[1] ?? null)
        ->filter()
        ->first();

    $active = $erroredGroup ?? request('tab', old('tab', $groups->keys()->first()));
@endphp

@section('content')
    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('put')

        <input type="hidden" name="tab" value="{{ $active }}">

        <div class="card card-primary card-outline">
            <div class="card-header p-0 border-bottom-0">
                <ul class="nav nav-tabs" role="tablist">
                    @foreach ($groups as $group => $settings)
                        <li class="nav-item">
                            <button class="nav-link {{ $group === $active ? 'active' : '' }}"
                                    data-bs-toggle="tab"
                                    data-bs-target="#tab-{{ $group }}"
                                    type="button"
                                    role="tab"
                                    onclick="document.querySelector('input[name=tab]').value = '{{ $group }}'">
                                {{ __(ucfirst($group)) }}
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="card-body">
                <div class="tab-content">
                    @foreach ($groups as $group => $settings)
                        <div class="tab-pane fade {{ $group === $active ? 'show active' : '' }}" id="tab-{{ $group }}" role="tabpanel">
                            @foreach ($settings as $setting)
                                @php
                                    $name = "settings[{$setting->group}][{$setting->key}]";
                                    $errorKey = "settings.{$setting->group}.{$setting->key}";
                                    $id = "setting-{$setting->group}-{$setting->key}";
                                    $current = old("settings.{$setting->group}.{$setting->key}", $setting->value);
                                    $label = ucfirst(str_replace('_', ' ', $setting->key));
                                @endphp

                                <div class="mb-3">
                                    @switch ($setting->type->inputType())
                                        @case ('checkbox')
                                            <div class="form-check">
                                                <input type="hidden" name="{{ $name }}" value="0">
                                                <input type="checkbox" id="{{ $id }}" name="{{ $name }}" value="1"
                                                       class="form-check-input @error($errorKey) is-invalid @enderror"
                                                       @checked((bool) $current)>
                                                <label class="form-check-label" for="{{ $id }}">{{ $label }}</label>
                                            </div>
                                            @break

                                        @case ('textarea')
                                            <label for="{{ $id }}" class="form-label">{{ $label }}</label>
                                            <textarea id="{{ $id }}" name="{{ $name }}" rows="4"
                                                      class="form-control font-monospace @error($errorKey) is-invalid @enderror">{{ $current }}</textarea>
                                            <div class="form-text">{{ __('Format JSON.') }}</div>
                                            @break

                                        @default
                                            <label for="{{ $id }}" class="form-label">{{ $label }}</label>
                                            <input type="{{ $setting->type->inputType() }}" id="{{ $id }}" name="{{ $name }}"
                                                   value="{{ $current }}"
                                                   class="form-control @error($errorKey) is-invalid @enderror">
                                    @endswitch

                                    @error($errorKey)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

                                    @if ($setting->is_public)
                                        <div class="form-text">
                                            <i class="bi bi-eye"></i> {{ __('Tampil di halaman undangan publik.') }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">{{ __('Simpan') }}</button>
            </div>
        </div>
    </form>
@endsection
