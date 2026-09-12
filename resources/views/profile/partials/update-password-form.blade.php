<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ __('Ubah kata sandi') }}</h3>
    </div>

    <form method="post" action="{{ route('password.update') }}">
        @csrf
        @method('put')

        <div class="card-body">
            @if (session('status') === 'password-updated')
                <div class="alert alert-success" role="alert">{{ __('Tersimpan.') }}</div>
            @endif

            <div class="mb-3">
                <label for="update_password_current_password" class="form-label">{{ __('Kata sandi saat ini') }}</label>
                <input id="update_password_current_password" name="current_password" type="password"
                       class="form-control @error('current_password', 'updatePassword') is-invalid @enderror"
                       autocomplete="current-password">
                @error('current_password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="update_password_password" class="form-label">{{ __('Kata sandi baru') }}</label>
                <input id="update_password_password" name="password" type="password"
                       class="form-control @error('password', 'updatePassword') is-invalid @enderror"
                       autocomplete="new-password">
                @error('password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="update_password_password_confirmation" class="form-label">{{ __('Ulangi kata sandi baru') }}</label>
                <input id="update_password_password_confirmation" name="password_confirmation" type="password"
                       class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror"
                       autocomplete="new-password">
                @error('password_confirmation', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="card-footer">
            <button type="submit" class="btn btn-primary">{{ __('Simpan') }}</button>
        </div>
    </form>
</div>
