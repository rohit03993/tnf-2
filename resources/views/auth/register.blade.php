<x-guest-layout subtitle="Create your account with mobile">
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if($whatsappHint)
        <div class="mb-4 rounded-tnf-lg border border-amber-200 bg-amber-50 px-4 py-3 text-tnf-sm text-amber-900">
            {{ $whatsappHint }}
        </div>
    @endif

    @if($step === 'otp')
        <form method="POST" action="{{ route('register.verify') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="phone" value="{{ old('phone', $phone) }}">
            <input type="hidden" name="name" value="{{ old('name', $name) }}">

            <div>
                <label class="tnf-auth-label">Name</label>
                <p class="tnf-auth-input bg-tnf-gray-light">{{ old('name', $name) }}</p>
            </div>

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

            <button type="submit" class="tnf-auth-submit">Verify &amp; create account</button>

            <div class="flex items-center justify-between text-tnf-sm">
                <a class="tnf-auth-link" href="{{ route('register', ['reset' => 1]) }}">Change details</a>
                <a class="tnf-auth-link" href="{{ route('login') }}">Already registered?</a>
            </div>
        </form>
    @else
        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="tnf-auth-label">Name <span class="text-tnf-red">*</span></label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                    autocomplete="name" class="tnf-auth-input" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <label for="phone" class="tnf-auth-label">Mobile number <span class="text-tnf-red">*</span></label>
                <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" required
                    autocomplete="tel" class="tnf-auth-input" placeholder="98765 43210 or +91…" />
                <p class="mt-1 text-tnf-sm text-tnf-muted">No email required. We’ll verify this number on WhatsApp.</p>
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>

            <button type="submit" class="tnf-auth-submit" @disabled(! $whatsappReady)>Send OTP on WhatsApp</button>

            <div class="flex items-center justify-between text-tnf-sm">
                <a class="tnf-auth-link" href="{{ route('login') }}">Already registered?</a>
            </div>
        </form>
    @endif
</x-guest-layout>
