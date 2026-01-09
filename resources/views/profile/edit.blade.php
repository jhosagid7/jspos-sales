@component('layouts.theme.app')
    @slot('header')
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    @endslot

    <div class="py-4">
        <div class="row">
            <div class="col-md-6">
                @include('profile.partials.update-profile-information-form')
            </div>
            <div class="col-md-6">
                @include('profile.partials.update-password-form')
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-md-12">
                @include('profile.partials.delete-user-form')
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <livewire:profile.logout-other-browser-sessions-form />
            </div>
        </div>
    </div>
@endcomponent
