<x-site.layout :seo="$seo">
    <div class="tnf-page-content mx-auto max-w-lg">
        <div class="rounded-tnf-lg bg-white p-8 text-center shadow-card">
            <p class="text-tnf-xs font-bold uppercase tracking-wider text-tnf-red">Subscriber ePaper</p>
            <h1 class="mt-2 text-tnf-xl font-bold text-tnf-navy">{{ $edition->title }}</h1>

            @if($edition->featuredMedia?->url())
                <img
                    src="{{ $edition->featuredMedia->url() }}"
                    alt=""
                    class="tnf-epaper-card-cover mx-auto mt-6 max-w-xs rounded-tnf-lg opacity-80"
                >
            @endif

            @if($isGuest ?? false)
                <p class="mt-6 text-tnf-base text-tnf-navy">
                    Sign in to read this subscriber edition of TNF Today.
                </p>
                <p class="mt-2 text-tnf-sm text-tnf-muted">
                    Older editions on the archive may be available without an account.
                </p>
            @else
                <p class="mt-6 text-tnf-base text-tnf-navy">
                    This edition is available to TNF Today subscribers only.
                </p>
                <p class="mt-2 text-tnf-sm text-tnf-muted">
                    Activate your membership to read the full digital newspaper.
                </p>
            @endif

            <div class="mt-6 flex flex-wrap justify-center gap-3">
                @if($isGuest ?? false)
                    <a href="{{ route('login', ['redirect_to' => request()->url()]) }}" class="tnf-btn-primary">Sign In</a>
                @else
                    <a href="{{ route('account') }}" class="tnf-btn-primary">My Account</a>
                @endif
                <a href="{{ route('epaper.index') }}" class="tnf-btn-outline">All editions</a>
            </div>
        </div>
    </div>
</x-site.layout>
