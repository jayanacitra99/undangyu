{{-- Shared by create and edit. `$template` is an unsaved model on create. --}}
@php
    /** @var \App\Models\Template $template */
    $jsonFlags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
    $selected = array_map('intval', (array) old('event_type_ids', $selectedEventTypeIds));
    $schemaValue = old('config_schema', $template->config_schema ? json_encode($template->config_schema, $jsonFlags) : '');
    $defaultsValue = old('default_config', $template->default_config ? json_encode($template->default_config, $jsonFlags) : '');
    $demoValue = old('demo_data', $template->demo_data ? json_encode($template->demo_data, $jsonFlags) : '');
@endphp

<div class="card mb-3">
    <div class="card-header">{{ __('Identitas') }}</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="name" class="form-label">{{ __('Nama') }}</label>
                <input type="text" id="name" name="name" value="{{ old('name', $template->name) }}"
                       class="form-control @error('name') is-invalid @enderror" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if ($template->exists)
                    <div class="form-text">{{ __('Slug :slug tidak berubah setelah template dibuat.', ['slug' => $template->slug]) }}</div>
                @else
                    <div class="form-text">{{ __('Slug dibuat otomatis dari nama.') }}</div>
                @endif
            </div>

            <div class="col-md-3">
                <label for="template_category_id" class="form-label">{{ __('Kategori') }}</label>
                <select id="template_category_id" name="template_category_id"
                        class="form-select @error('template_category_id') is-invalid @enderror" required>
                    <option value="">{{ __('Pilih kategori') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}"
                            @selected((int) old('template_category_id', $template->template_category_id) === $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                @error('template_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3">
                <label for="status" class="form-label">{{ __('Status') }}</label>
                <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}"
                            @selected(old('status', $template->status?->value) === $status->value)>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <label for="description" class="form-label">{{ __('Deskripsi') }}</label>
                <textarea id="description" name="description" rows="2"
                          class="form-control @error('description') is-invalid @enderror">{{ old('description', $template->description) }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label for="view_key" class="form-label">{{ __('View key') }}</label>
                <input type="text" id="view_key" name="view_key" value="{{ old('view_key', $template->view_key) }}"
                       class="form-control @error('view_key') is-invalid @enderror" required>
                @error('view_key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">{{ __('Folder komponen Vue di resources/js/invitation/templates/.') }}</div>
            </div>

            <div class="col-md-4">
                <label for="version" class="form-label">{{ __('Versi') }}</label>
                <input type="text" id="version" name="version" value="{{ old('version', $template->version) }}"
                       class="form-control @error('version') is-invalid @enderror" placeholder="1.0.0" required>
                @error('version')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label for="sort_order" class="form-label">{{ __('Urutan') }}</label>
                <input type="number" id="sort_order" name="sort_order" min="0"
                       value="{{ old('sort_order', $template->sort_order ?? 0) }}"
                       class="form-control @error('sort_order') is-invalid @enderror">
                @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">{{ __('Jenis acara dan harga') }}</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <span class="form-label d-block">{{ __('Jenis acara yang didukung') }}</span>
                <div class="row row-cols-2 g-1 @error('event_type_ids') border border-danger rounded p-2 @enderror">
                    @foreach ($eventTypes as $eventType)
                        <div class="col">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input"
                                       id="event_type_{{ $eventType->id }}"
                                       name="event_type_ids[]" value="{{ $eventType->id }}"
                                       @checked(in_array($eventType->id, $selected, true))>
                                <label class="form-check-label" for="event_type_{{ $eventType->id }}">{{ $eventType->name }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>
                @error('event_type_ids')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3">
                <label for="extra_price" class="form-label">{{ __('Harga tambahan (IDR)') }}</label>
                <input type="number" id="extra_price" name="extra_price" min="0" step="0.01"
                       value="{{ old('extra_price', $template->extra_price ?? 0) }}"
                       class="form-control @error('extra_price') is-invalid @enderror">
                @error('extra_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3 d-flex align-items-end">
                <div class="form-check form-switch mb-2">
                    <input type="hidden" name="is_premium" value="0">
                    <input type="checkbox" id="is_premium" name="is_premium" value="1" class="form-check-input"
                           @checked(old('is_premium', $template->is_premium ?? false))>
                    <label class="form-check-label" for="is_premium">{{ __('Premium') }}</label>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">{{ __('Gambar') }}</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label for="thumbnail" class="form-label">{{ __('Thumbnail') }}</label>
                <input type="file" id="thumbnail" name="thumbnail" accept="image/*"
                       class="form-control @error('thumbnail') is-invalid @enderror"
                       @required(! $template->exists)>
                @error('thumbnail')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if ($template->exists)
                    <div class="form-text">{{ __('Kosongkan untuk mempertahankan gambar sekarang.') }}</div>
                    <img src="{{ Storage::disk('public')->url($template->thumbnail) }}" alt=""
                         class="img-fluid rounded mt-2" style="max-height: 160px">
                @endif
            </div>

            <div class="col-md-8">
                <label for="screenshots" class="form-label">{{ __('Tambah screenshot') }}</label>
                <input type="file" id="screenshots" name="screenshots[]" accept="image/*" multiple
                       class="form-control @error('screenshots.*') is-invalid @enderror">
                @error('screenshots.*')<div class="invalid-feedback">{{ $message }}</div>@enderror

                @if ($template->exists && $template->screenshots->isNotEmpty())
                    <ol class="list-group list-group-numbered mt-3">
                        @foreach ($template->screenshots as $screenshot)
                            <li class="list-group-item d-flex align-items-center gap-3">
                                <input type="hidden" name="screenshot_order[]" value="{{ $screenshot->id }}">
                                <img src="{{ Storage::disk('public')->url($screenshot->path) }}" alt=""
                                     class="rounded" style="height: 48px">
                                <span class="flex-grow-1">{{ $screenshot->caption ?: $screenshot->path }}</span>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input"
                                           id="remove_{{ $screenshot->id }}"
                                           name="remove_screenshots[]" value="{{ $screenshot->id }}">
                                    <label class="form-check-label" for="remove_{{ $screenshot->id }}">{{ __('Hapus') }}</label>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                    <div class="form-text">{{ __('Urutan mengikuti daftar di atas.') }}</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">{{ __('Konfigurasi') }}</div>
    <div class="card-body">
        <p class="text-secondary small">
            {{ __('JSON mentah. Editor visual di luar lingkup sesi ini. Bentuk skema ada di docs/05 bagian 5.') }}
        </p>

        <div class="row g-3">
            <div class="col-md-6">
                <label for="config_schema" class="form-label">config_schema</label>
                <textarea id="config_schema" name="config_schema" rows="14" spellcheck="false"
                          class="form-control font-monospace @error('config_schema') is-invalid @enderror" required>{{ $schemaValue }}</textarea>
                @error('config_schema')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="default_config" class="form-label">default_config</label>
                <textarea id="default_config" name="default_config" rows="14" spellcheck="false"
                          class="form-control font-monospace @error('default_config') is-invalid @enderror" required>{{ $defaultsValue }}</textarea>
                @error('default_config')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <label for="demo_data" class="form-label">demo_data</label>
                <textarea id="demo_data" name="demo_data" rows="6" spellcheck="false"
                          class="form-control font-monospace @error('demo_data') is-invalid @enderror">{{ $demoValue }}</textarea>
                @error('demo_data')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">{{ __('Opsional. Mengisi pratinjau publik.') }}</div>
            </div>
        </div>
    </div>

    <div class="card-footer d-flex gap-2">
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
        <a href="{{ route('admin.templates.index') }}" class="btn btn-outline-secondary">{{ __('Batal') }}</a>
    </div>
</div>
