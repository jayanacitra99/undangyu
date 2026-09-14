{{-- Shared by create and edit. `$category` is an unsaved model on create. --}}
<div class="card">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="name" class="form-label">{{ __('Nama') }}</label>
                <input type="text" id="name" name="name" value="{{ old('name', $category->name) }}"
                       class="form-control @error('name') is-invalid @enderror" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="slug" class="form-label">{{ __('Slug') }}</label>
                <input type="text" id="slug" name="slug" value="{{ old('slug', $category->slug) }}"
                       class="form-control @error('slug') is-invalid @enderror"
                       placeholder="{{ __('Kosongkan untuk mengikuti nama') }}">
                @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <label for="description" class="form-label">{{ __('Deskripsi') }}</label>
                <textarea id="description" name="description" rows="3"
                          class="form-control @error('description') is-invalid @enderror">{{ old('description', $category->description) }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="sort_order" class="form-label">{{ __('Urutan') }}</label>
                <input type="number" id="sort_order" name="sort_order" min="0"
                       value="{{ old('sort_order', $category->sort_order ?? 0) }}"
                       class="form-control @error('sort_order') is-invalid @enderror">
                @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" id="is_active" name="is_active" value="1" class="form-check-input"
                           @checked(old('is_active', $category->is_active ?? true))>
                    <label class="form-check-label" for="is_active">{{ __('Aktif') }}</label>
                </div>
            </div>
        </div>
    </div>

    <div class="card-footer d-flex gap-2">
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
        <a href="{{ route('admin.template-categories.index') }}" class="btn btn-outline-secondary">{{ __('Batal') }}</a>
    </div>
</div>
