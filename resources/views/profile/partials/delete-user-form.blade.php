{{--
    Account deletion. docs/03 § 3.1 soft-deletes the row, but it still signs the
    user out and hides every invitation, so it asks twice: a Bootstrap modal and
    the password.
--}}
<div class="card card-danger card-outline">
    <div class="card-header">
        <h3 class="card-title">{{ __('Hapus akun') }}</h3>
    </div>

    <div class="card-body">
        <p class="mb-0">
            {{ __('Setelah akun dihapus, seluruh data di dalamnya tidak lagi bisa diakses. Unduh apa pun yang masih Anda perlukan sebelum melanjutkan.') }}
        </p>
    </div>

    <div class="card-footer">
        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirm-user-deletion">
            {{ __('Hapus akun') }}
        </button>
    </div>
</div>

<div class="modal fade" id="confirm-user-deletion" tabindex="-1" aria-labelledby="confirm-user-deletion-label" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="{{ route('profile.destroy') }}" class="modal-content">
            @csrf
            @method('delete')

            <div class="modal-header">
                <h5 class="modal-title" id="confirm-user-deletion-label">{{ __('Hapus akun ini?') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Tutup') }}"></button>
            </div>

            <div class="modal-body">
                <p>{{ __('Masukkan kata sandi Anda untuk memastikan penghapusan ini disengaja.') }}</p>

                <label for="delete_password" class="form-label">{{ __('Kata sandi') }}</label>
                <input id="delete_password" name="password" type="password"
                       class="form-control @error('password', 'userDeletion') is-invalid @enderror"
                       autocomplete="current-password">
                @error('password', 'userDeletion')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                <button type="submit" class="btn btn-danger">{{ __('Hapus akun') }}</button>
            </div>
        </form>
    </div>
</div>

@if ($errors->userDeletion->isNotEmpty())
    @push('scripts')
        <script>
            // A failed confirmation redirects back here — reopen the modal so the
            // error is where the user left off.
            document.addEventListener('DOMContentLoaded', () => {
                new window.bootstrap.Modal(document.getElementById('confirm-user-deletion')).show();
            });
        </script>
    @endpush
@endif
