<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if(! $whatsappReady)
        <div class="mb-4 rounded-tnf-lg border border-amber-200 bg-amber-50 px-4 py-3 text-tnf-sm text-amber-900">
            Phone login needs WhatsApp API to be connected in Admin → Settings → WhatsApp API.
        </div>
    @endif

    @if($step === 'otp')
        <form method="POST" action="{{ route('login.phone.verify') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="phone" value="{{ old('phone', $phone) }}">

            <div>
                <label class="tnf-auth-label">Mobile number</label>
                <p class="tnf-auth-input bg-tnf-gray-light">{{ \App\Support\PhoneNumber::formatDisplay(old('phone', $phone)) }}</p>
            </div>

            <div>
                <label for="otp" class="tnf-auth-label">WhatsApp OTP</label>
                <input id="otp" type="text" name="otp" inputmode="numeric" pattern="[0-9]*" maxlength="6"
                    required autofocus autocomplete="one-time-code" class="tnf-auth-input" placeholder="6-digit code" />
                <x-input-error :messages="$errors->get('otp')" class="mt-2" />
            </div>

            <button type="submit" class="tnf-auth-submit">Verify &amp; log in</button>

            <div class="flex items-center justify-between text-tnf-sm">
                <a class="tnf-auth-link" href="{{ route('login.phone', ['reset' => 1]) }}">Change number</a>
                <a class="tnf-auth-link" href="{{ route('login') }}">Email login</a>
            </div>
        </form>
    @else
        <form method="POST" action="{{ route('login.phone.request') }}" class="space-y-4">
            @csrf

            <div>
                <label for="phone" class="tnf-auth-label">Mobile number</label>
                <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" required autofocus
                    autocomplete="tel" class="tnf-auth-input" placeholder="98765 43210 or +91…" />
                <p class="mt-1 text-tnf-sm text-tnf-muted">We’ll send a one-time code on WhatsApp.</p>
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>

            <button type="submit" class="tnf-auth-submit" @disabled(! $whatsappReady)>Send OTP on WhatsApp</button>

            <div class="flex items-center justify-between text-tnf-sm">
                <a class="tnf-auth-link" href="{{ route('login') }}">Email login</a>
                @if (Route::has('register') && config('tnf.allow_public_registration'))
                    <a class="tnf-auth-link" href="{{ route('register') }}">Register</a>
                @endif
            </div>
        </form>
    @endif
</x-guest-layout>
