{{-- Shared by create and edit. `$eventType` is an unsaved model on create. --}}
<div class="card">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="name" class="form-label">{{ __('Nama') }}</label>
                <input type="text" id="name" name="name" value="{{ old('name', $eventType->name) }}"
                       class="form-control @error('name') is-invalid @enderror" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="slug" class="form-label">{{ __('Slug') }}</label>
                <input type="text" id="slug" name="slug" value="{{ old('slug', $eventType->slug) }}"
                       class="form-control @error('slug') is-invalid @enderror"
                       placeholder="{{ __('Kosongkan untuk mengikuti nama') }}">
                @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="icon" class="form-label">{{ __('Ikon') }}</label>
                <input type="text" id="icon" name="icon" value="{{ old('icon', $eventType->icon) }}"
                       class="form-control @error('icon') is-invalid @enderror" placeholder="bi-heart">
                <div class="form-text">{{ __('Nama kelas Bootstrap Icons.') }}</div>
                @error('icon')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="sort_order" class="form-label">{{ __('Urutan') }}</label>
                <input type="number" id="sort_order" name="sort_order" min="0"
                       value="{{ old('sort_order', $eventType->sort_order ?? 0) }}"
                       class="form-control @error('sort_order') is-invalid @enderror">
                <div class="form-text">{{ __('Bisa juga diatur dengan menggeser baris di daftar.') }}</div>
                @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <fieldset>
                    <legend class="form-label fs-6">{{ __('Peran orang') }}</legend>
                    @error('person_roles')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                    @foreach ($personRoles as $role)
                        <div class="form-check">
                            <input type="checkbox" id="role-{{ $role->value }}" name="person_roles[]"
                                   value="{{ $role->value }}" class="form-check-input"
                                   @checked(in_array($role->value, old('person_roles', $eventType->person_roles ?? []), true))>
                            <label class="form-check-label" for="role-{{ $role->value }}">
                                {{ $role->label() }} <span class="text-secondary">({{ $role->value }})</span>
                            </label>
                        </div>
                    @endforeach
                </fieldset>
            </div>

            <div class="col-md-6">
                <fieldset>
                    <legend class="form-label fs-6">{{ __('Seksi bawaan') }}</legend>
                    @error('default_sections')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                    @foreach ($sectionKeys as $section)
                        <div class="form-check">
                            <input type="checkbox" id="section-{{ $section->value }}" name="default_sections[]"
                                   value="{{ $section->value }}" class="form-check-input"
                                   @checked(in_array($section->value, old('default_sections', $eventType->default_sections ?? []), true))>
                            <label class="form-check-label" for="section-{{ $section->value }}">
                                {{ $section->label() }} <span class="text-secondary">({{ $section->value }})</span>
                            </label>
                        </div>
                    @endforeach
                </fieldset>
            </div>

            <div class="col-12">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" id="is_active" name="is_active" value="1" class="form-check-input"
                           @checked(old('is_active', $eventType->is_active ?? true))>
                    <label class="form-check-label" for="is_active">{{ __('Aktif') }}</label>
                </div>
            </div>
        </div>
    </div>

    <div class="card-footer d-flex gap-2">
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
        <a href="{{ route('admin.event-types.index') }}" class="btn btn-outline-secondary">{{ __('Batal') }}</a>
    </div>
</div>
