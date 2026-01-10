<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">{{ __('Profile Information') }}</h3>
    </div>
    
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="form-horizontal" enctype="multipart/form-data">
        @csrf
        @method('patch')

        <div class="card-body">
            <p class="text-muted">
                {{ __("Update your account's profile information and email address.") }}
            </p>

            <div class="form-group">
                <label for="photo">{{ __('Profile Photo') }}</label>
                <div class="mb-2">
                    @if ($user->profile_photo_path)
                        <img src="{{ asset('storage/' . $user->profile_photo_path) }}" alt="{{ $user->name }}" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">
                    @else
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&color=7F9CF5&background=EBF4FF" alt="{{ $user->name }}" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">
                    @endif
                </div>
                <input type="file" class="form-control-file @error('photo') is-invalid @enderror" id="photo" name="photo">
                @error('photo')
                    <span class="error invalid-feedback d-block">{{ $message }}</span>
                @enderror
            </div>
            <div class="form-group">
                <label for="name">{{ __('Name') }}</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
                @error('name')
                    <span class="error invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="email">{{ __('Email') }}</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="username">
                @error('email')
                    <span class="error invalid-feedback">{{ $message }}</span>
                @enderror

                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <div class="mt-2">
                        <p class="text-sm text-danger">
                            {{ __('Your email address is unverified.') }}

                            <button form="send-verification" class="btn btn-link p-0 m-0 align-baseline">
                                {{ __('Click here to re-send the verification email.') }}
                            </button>
                        </p>

                        @if (session('status') === 'verification-link-sent')
                            <p class="text-success font-weight-bold mt-2">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div class="card-footer">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>

            @if (session('status') === 'profile-updated')
                <span class="text-success ml-3 fade-out" style="display: inline-block;">
                    {{ __('Saved.') }}
                </span>
                <script>
                    setTimeout(function() {
                        document.querySelector('.fade-out').style.display = 'none';
                    }, 2000);
                </script>
            @endif
        </div>
    </form>
</div>
