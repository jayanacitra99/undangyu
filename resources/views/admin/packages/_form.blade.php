{{-- Shared by create and edit. `$package` is an unsaved model on create. --}}
@php
    /** @var \App\Models\Package $package */
    $rows = old('features') !== null
        ? array_values(old('features'))
        : $featureRows;
@endphp

<div class="card mb-3">
    <div class="card-header">{{ __('Identitas') }}</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="name" class="form-label">{{ __('Nama') }}</label>
                <input type="text" id="name" name="name" value="{{ old('name', $package->name) }}"
                       class="form-control @error('name') is-invalid @enderror" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if ($package->exists)
                    <div class="form-text">{{ __('Slug :slug tidak berubah setelah paket dibuat.', ['slug' => $package->slug]) }}</div>
                @else
                    <div class="form-text">{{ __('Slug dibuat otomatis dari nama.') }}</div>
                @endif
            </div>

            <div class="col-md-3">
                <label for="active_days" class="form-label">{{ __('Masa aktif (hari)') }}</label>
                <input type="number" id="active_days" name="active_days" min="1"
                       value="{{ old('active_days', $package->active_days) }}"
                       class="form-control @error('active_days') is-invalid @enderror" required>
                @error('active_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3">
                <label for="sort_order" class="form-label">{{ __('Urutan') }}</label>
                <input type="number" id="sort_order" name="sort_order" min="0"
                       value="{{ old('sort_order', $package->sort_order ?? 0) }}"
                       class="form-control @error('sort_order') is-invalid @enderror">
                @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <label for="description" class="form-label">{{ __('Deskripsi') }}</label>
                <textarea id="description" name="description" rows="2"
                          class="form-control @error('description') is-invalid @enderror">{{ old('description', $package->description) }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">{{ __('Harga') }}</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label for="price" class="form-label">{{ __('Harga normal') }}</label>
                <input type="number" id="price" name="price" min="0" step="0.01"
                       value="{{ old('price', $package->price ?? 0) }}"
                       class="form-control @error('price') is-invalid @enderror" required>
                @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label for="discount_price" class="form-label">{{ __('Harga diskon') }}</label>
                <input type="number" id="discount_price" name="discount_price" min="0" step="0.01"
                       value="{{ old('discount_price', $package->discount_price) }}"
                       class="form-control @error('discount_price') is-invalid @enderror">
                @error('discount_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">{{ __('Kosongkan bila tidak ada diskon.') }}</div>
            </div>

            <div class="col-md-4">
                <label for="currency" class="form-label">{{ __('Mata uang') }}</label>
                <input type="text" id="currency" name="currency" maxlength="3"
                       value="{{ old('currency', $package->currency ?? 'IDR') }}"
                       class="form-control @error('currency') is-invalid @enderror" required>
                @error('currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_featured" value="0">
                    <input type="checkbox" id="is_featured" name="is_featured" value="1" class="form-check-input"
                           @checked(old('is_featured', $package->is_featured ?? false))>
                    <label class="form-check-label" for="is_featured">{{ __('Tandai sebagai paket unggulan') }}</label>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" id="is_active" name="is_active" value="1" class="form-check-input"
                           @checked(old('is_active', $package->is_active ?? true))>
                    <label class="form-check-label" for="is_active">{{ __('Aktif') }}</label>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">{{ __('Fitur') }}</div>
    <div class="card-body">
        <p class="text-secondary small">
            {{ __('Kunci fitur berasal dari App\Enums\FeatureKey. Kuota butuh angka; sisanya hanya aktif atau tidak.') }}
        </p>

        <div
            data-island="feature-flags"
            data-feature-keys='@json($featureKeys)'
            data-rows='@json($rows)'
            data-errors='@json($errors->getMessages())'
        ></div>
    </div>

    <div class="card-footer d-flex gap-2">
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
        <a href="{{ route('admin.packages.index') }}" class="btn btn-outline-secondary">{{ __('Batal') }}</a>
    </div>
</div>
