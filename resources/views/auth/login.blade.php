<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="tnf-auth-label">{{ __('Email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                autocomplete="username" class="tnf-auth-input" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label for="password" class="tnf-auth-label">{{ __('Password') }}</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                class="tnf-auth-input" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center">
            <input id="remember_me" type="checkbox" name="remember"
                class="rounded border-tnf-gray-dark text-tnf-red focus:ring-tnf-red" />
            <label for="remember_me" class="ms-2 text-tnf-sm text-tnf-muted">{{ __('Remember me') }}</label>
        </div>

        <button type="submit" class="tnf-auth-submit">{{ __('Log in') }}</button>

        <div class="flex items-center justify-between text-tnf-sm">
            @if (Route::has('password.request'))
                <a class="tnf-auth-link" href="{{ route('password.request') }}">{{ __('Forgot password?') }}</a>
            @endif
            @if (Route::has('register') && config('tnf.allow_public_registration'))
                <a class="tnf-auth-link" href="{{ route('register') }}">{{ __('Register') }}</a>
            @endif
        </div>
    </form>
</x-guest-layout>
