<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ __('Informasi profil') }}</h3>
    </div>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('patch')

        <div class="card-body">
            @if (session('status') === 'profile-updated')
                <div class="alert alert-success" role="alert">{{ __('Tersimpan.') }}</div>
            @endif

            <div class="mb-3">
                <label for="name" class="form-label">{{ __('Nama') }}</label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}"
                       class="form-control @error('name') is-invalid @enderror"
                       required autofocus autocomplete="name">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">{{ __('Email') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}"
                       class="form-control @error('email') is-invalid @enderror"
                       required autocomplete="username">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror

                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <p class="form-text mb-0">
                        {{ __('Alamat email Anda belum diverifikasi.') }}
                        <button form="send-verification" class="btn btn-link p-0 align-baseline">
                            {{ __('Kirim ulang email verifikasi.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="text-success small mb-0">
                            {{ __('Tautan verifikasi baru sudah dikirim ke email Anda.') }}
                        </p>
                    @endif
                @endif
            </div>
        </div>

        <div class="card-footer">
            <button type="submit" class="btn btn-primary">{{ __('Simpan') }}</button>
        </div>
    </form>
</div>
