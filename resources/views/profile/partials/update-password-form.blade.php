<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">{{ __('Update Password') }}</h3>
    </div>

    <form method="post" action="{{ route('password.update') }}" class="form-horizontal">
        @csrf
        @method('put')

        <div class="card-body">
            <p class="text-muted">
                {{ __('Ensure your account is using a long, random password to stay secure.') }}
            </p>

            <div class="form-group">
                <label for="update_password_current_password">{{ __('Current Password') }}</label>
                <input type="password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" id="update_password_current_password" name="current_password" autocomplete="current-password">
                @error('current_password', 'updatePassword')
                    <span class="error invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="update_password_password">{{ __('New Password') }}</label>
                <input type="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror" id="update_password_password" name="password" autocomplete="new-password">
                @error('password', 'updatePassword')
                    <span class="error invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="update_password_password_confirmation">{{ __('Confirm Password') }}</label>
                <input type="password" class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror" id="update_password_password_confirmation" name="password_confirmation" autocomplete="new-password">
                @error('password_confirmation', 'updatePassword')
                    <span class="error invalid-feedback">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="card-footer">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>

            @if (session('status') === 'password-updated')
                <span class="text-success ml-3 fade-out-password" style="display: inline-block;">
                    {{ __('Saved.') }}
                </span>
                <script>
                    setTimeout(function() {
                        document.querySelector('.fade-out-password').style.display = 'none';
                    }, 2000);
                </script>
            @endif
        </div>
    </form>
</div>
