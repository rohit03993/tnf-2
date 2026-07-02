<x-site.layout :auth-lite="true" title="Profile — TNF Today">
    <div class="tnf-page-content mx-auto max-w-3xl space-y-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('account') }}" class="text-tnf-sm font-semibold text-tnf-red hover:text-tnf-red-dark">
                &larr; Back to My Account
            </a>
        </div>

        @if(session('status') === 'profile-updated')
            <div class="rounded-tnf-lg border border-green-200 bg-green-50 px-4 py-3 text-tnf-sm text-green-800">
                Profile updated successfully.
            </div>
        @endif

        @if(session('status') === 'password-updated')
            <div class="rounded-tnf-lg border border-green-200 bg-green-50 px-4 py-3 text-tnf-sm text-green-800">
                Password updated successfully.
            </div>
        @endif

        <div class="rounded-tnf-lg bg-white p-6 shadow-card">
            <h1 class="tnf-section-title mb-2">Profile &amp; account settings</h1>
            <p class="text-tnf-sm text-tnf-muted">Update your details, change your password, or permanently delete your account.</p>
        </div>

        <div class="rounded-tnf-lg bg-white p-6 shadow-card">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="rounded-tnf-lg bg-white p-6 shadow-card">
            @include('profile.partials.update-password-form')
        </div>

        <div class="rounded-tnf-lg border border-red-200 bg-white p-6 shadow-card">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</x-site.layout>
