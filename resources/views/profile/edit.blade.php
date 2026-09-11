{{--
    Account settings. Served to every signed-in role, so it extends the shell
    directly and lets App\Support\Menu pick the sidebar that fits the user.
--}}
@extends('layouts.adminlte')

@section('title', __('Profil'))

@section('content')
    <div class="row">
        <div class="col-12 col-xl-8">
            @include('profile.partials.update-profile-information-form')
            @include('profile.partials.update-password-form')
            @include('profile.partials.delete-user-form')
        </div>
    </div>
@endsection
