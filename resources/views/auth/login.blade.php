<x-guest-layout>

    @if ($errors->any())
        <div>
            <div class="font-medium text-red-600">
                {{ __('Whoops!') }}
            </div>

            <ul class="mt-3 list-disc list-inside text-sm text-red-600">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <h4 class="text-center"><b>Ingresar al Sistema</b></h4>

    <form method="POST" action="{{ route('login') }}" class="mt-3">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')"
                required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required
                autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>



        <div class="flex items-center justify-end mt-4">
            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>

        </div>
        <div class="text-center">
            <a href="https://github.com/jhosagid7" style="font-size: 12px">jhonnypirela.dev</a>
        </div>
    </form>
</x-guest-layout>
