@props(['disclaimer', 'email', 'credits', 'logo' => null])

<footer class="tnf-footer">
    <div class="tnf-footer-accent" aria-hidden="true"></div>

    <div class="tnf-footer-inner">
        <div class="tnf-footer-grid">
            <div class="tnf-footer-brand">
                <a href="{{ route('home') }}" class="tnf-footer-logo">
                    @if(filled($logo))
                        <x-site.brand-mark :logo="$logo" size="footer" :show-wordmark="false" loading="lazy" />
                    @else
                        <span class="tnf-footer-logo-mark">TNF</span>
                        <span class="tnf-footer-logo-text">{{ config('app.name') }}</span>
                    @endif
                </a>
                <p class="tnf-footer-tagline">
                    News, videos, and digital ePaper — curated for readers across India.
                </p>
                @if(filled($email))
                    <a href="mailto:{{ $email }}" class="tnf-footer-email">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        {{ $email }}
                    </a>
                @endif
            </div>

            <div class="tnf-footer-nav">
                <div class="tnf-footer-col">
                    <h3 class="tnf-footer-heading">Explore</h3>
                    <ul class="tnf-footer-links">
                        <li><a href="{{ route('home') }}">Home</a></li>
                        <li><a href="{{ route('videos.index') }}">Videos</a></li>
                        <li><a href="{{ route('epaper.index') }}">ePaper</a></li>
                    </ul>
                </div>

                <div class="tnf-footer-col">
                    <h3 class="tnf-footer-heading">Company</h3>
                    <ul class="tnf-footer-links">
                        <li><a href="{{ route('page.about') }}">About Us</a></li>
                        <li><a href="{{ route('page.contact') }}">Contact Us</a></li>
                        <li class="hidden lg:list-item">
                            <button type="button" class="tnf-footer-install-link" data-tnf-pwa-install hidden>
                                Install App
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="tnf-footer-col">
                    <h3 class="tnf-footer-heading">Legal</h3>
                    <ul class="tnf-footer-links">
                        <li><a href="{{ route('page.terms') }}">Terms of Use</a></li>
                        <li><a href="{{ route('page.privacy') }}">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
        </div>

        @if(filled($disclaimer))
            <div class="tnf-footer-disclaimer prose max-w-none">
                {!! $disclaimer !!}
            </div>
        @endif

        <div class="tnf-footer-bottom">
            <p class="tnf-footer-copyright">
                &copy; {{ date('Y') }} {{ config('app.name') }}.
                @if(filled($credits))
                    <span class="tnf-footer-credits">{{ $credits }}</span>
                @endif
            </p>
        </div>
    </div>
</footer>
