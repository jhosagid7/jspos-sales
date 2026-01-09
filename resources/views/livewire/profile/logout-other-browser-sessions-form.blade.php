<div class="card card-warning">
    <div class="card-header">
        <h3 class="card-title">{{ __('Browser Sessions') }}</h3>
    </div>

    <div class="card-body">
        <p class="text-muted">
            {{ __('Manage and log out your active sessions on other browsers and devices.') }}
        </p>

        <p class="text-muted">
            {{ __('If necessary, you may log out of all of your other browser sessions across all of your devices. Some of your recent sessions are listed below; however, this list may not be exhaustive. If you feel your account has been compromised, you should also update your password.') }}
        </p>

        @if (count($this->sessions) > 0)
            <div class="mt-4 space-y-6">
                <!-- Other Browser Sessions -->
                @foreach ($this->sessions as $session)
                    <div class="d-flex align-items-center mb-3">
                        <div class="mr-3">
                            @if ($session->agent['is_desktop'])
                                <i class="fas fa-desktop fa-2x text-muted"></i>
                            @else
                                <i class="fas fa-mobile-alt fa-2x text-muted"></i>
                            @endif
                        </div>

                        <div>
                            <div class="text-sm text-gray-600">
                                {{ $session->agent['platform'] ? $session->agent['platform'] : 'Unknown' }} - {{ $session->agent['browser'] ? $session->agent['browser'] : 'Unknown' }}
                            </div>

                            <div>
                                <div class="text-xs text-muted">
                                    {{ $session->ip_address }},

                                    @if ($session->is_current_device)
                                        <span class="text-success font-weight-bold">{{ __('This device') }}</span>
                                    @else
                                        {{ __('Last active') }} {{ $session->last_active }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="mt-4">
            <button type="button" class="btn btn-dark" wire:click="confirmLogout" wire:loading.attr="disabled">
                {{ __('Log Out Other Browser Sessions') }}
            </button>

            <span wire:loading wire:target="confirmLogout" class="ml-2 text-muted">
                {{ __('Processing...') }}
            </span>
        </div>
    </div>

    <!-- Log Out Other Devices Confirmation Modal -->
    <div class="modal fade" id="confirmLogoutModal" tabindex="-1" role="dialog" aria-labelledby="confirmLogoutModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmLogoutModalLabel">{{ __('Log Out Other Browser Sessions') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">
                        {{ __('Please enter your password to confirm you would like to log out of your other browser sessions across all of your devices.') }}
                    </p>

                    <div class="form-group">
                        <input type="password" class="form-control @error('password') is-invalid @enderror" placeholder="{{ __('Password') }}" wire:model="password" wire:keydown.enter="logoutOtherBrowserSessions">
                        @error('password')
                            <span class="error invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-primary" wire:click="logoutOtherBrowserSessions" wire:loading.attr="disabled">
                        {{ __('Log Out Other Browser Sessions') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('open-modal', (event) => {
                $('#confirmLogoutModal').modal('show');
            });

            Livewire.on('close-modal', (event) => {
                $('#confirmLogoutModal').modal('hide');
            });
        });
    </script>
</div>
