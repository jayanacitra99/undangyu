{{--
    Builder shell (16.2).

    One route, one content pane, a tab in the query string. On phones the tab
    strip is a select — eight pills do not fit on a 375px screen, and clients
    edit these on phones.
--}}
@extends('layouts.client')

@section('title', $invitation->title)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('client.invitations.index') }}">{{ __('Undangan') }}</a></li>
    <li class="breadcrumb-item active">{{ $tabs[$tab]['label'] }}</li>
@endsection

@section('content')
    @unless ($canEdit)
        <div class="alert alert-warning" role="alert">
            <i class="bi bi-lock me-1"></i>
            {{ __('Undangan ini sedang ditangguhkan, jadi hanya bisa dilihat. Hubungi dukungan untuk membukanya.') }}
        </div>
    @endunless

    <div class="row g-3">
        <div class="col-lg-3 order-1">
            @include('client.invitations.partials.tab-nav')

            {{--
                On a phone the checklist goes below the editor: the client came
                here to type, and three screens of "still missing" before the
                first field is a scroll they did not ask for.
            --}}
            <div class="d-none d-lg-block">
                @include('client.invitations.partials.completeness')
            </div>
        </div>

        <div class="col-lg-9 order-2">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0">
                        <i class="bi {{ $tabs[$tab]['icon'] }} me-1"></i>{{ $tabs[$tab]['label'] }}
                    </h3>
                    <span class="badge {{ $invitation->status->badgeClass() }}">
                        {{ $invitation->status->label() }}
                    </span>
                </div>
                <div class="card-body">
                    @includeFirst(
                        ['client.invitations.tabs.'.$tab, 'client.invitations.tabs.placeholder'],
                        ['tabLabel' => $tabs[$tab]['label']]
                    )
                </div>
            </div>
        </div>

        <div class="col-12 order-3 d-lg-none">
            @include('client.invitations.partials.completeness')
        </div>
    </div>

    {{--
        Sticky preview button. The public renderer arrives in Session 21, so
        this is disabled rather than pointed at a route that would 404.
    --}}
    <div class="position-sticky bottom-0 py-3 d-flex justify-content-end pe-1" style="z-index: 1020;">
        <button type="button" class="btn btn-primary shadow rounded-pill px-4" disabled
                title="{{ __('Pratinjau hadir bersama penampil undangan.') }}">
            <i class="bi bi-eye me-1"></i>{{ __('Pratinjau') }}
        </button>
    </div>
@endsection
