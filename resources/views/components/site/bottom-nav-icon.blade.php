@props(['name'])

@switch($name)
    @case('home')
        <svg {{ $attributes->merge(['class' => 'tnf-bottom-nav-icon']) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M4.75 10.75L12 5l7.25 5.75V19a1.25 1.25 0 01-1.25 1.25h-4.25v-5.5h-3.5v5.5H6A1.25 1.25 0 014.75 19v-8.25z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
        </svg>
        @break

    @case('videos')
        <svg {{ $attributes->merge(['class' => 'tnf-bottom-nav-icon']) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <rect x="4.5" y="6.5" width="15" height="11" rx="2.25" stroke="currentColor" stroke-width="1.75"/>
            <path d="M10.25 9.75v4.5l4.25-2.25-4.25-2.25z" fill="currentColor"/>
        </svg>
        @break

    @case('epaper')
        <svg {{ $attributes->merge(['class' => 'tnf-bottom-nav-icon']) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M5.5 5.5h13a1 1 0 011 1v11a1 1 0 01-1 1h-13a1 1 0 01-1-1v-11a1 1 0 011-1z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
            <path d="M6.75 9h10.5M6.75 12h10.5M6.75 15h7" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
            <path d="M12 5.5v13" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
        </svg>
        @break

    @case('menu')
        <svg {{ $attributes->merge(['class' => 'tnf-bottom-nav-icon']) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <rect x="4.5" y="4.5" width="6.5" height="6.5" rx="1.25" stroke="currentColor" stroke-width="1.75"/>
            <rect x="13" y="4.5" width="6.5" height="6.5" rx="1.25" stroke="currentColor" stroke-width="1.75"/>
            <rect x="4.5" y="13" width="6.5" height="6.5" rx="1.25" stroke="currentColor" stroke-width="1.75"/>
            <rect x="13" y="13" width="6.5" height="6.5" rx="1.25" stroke="currentColor" stroke-width="1.75"/>
        </svg>
        @break

    @case('account')
        <svg {{ $attributes->merge(['class' => 'tnf-bottom-nav-icon']) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="12" cy="8.25" r="3.75" stroke="currentColor" stroke-width="1.75"/>
            <path d="M5.5 19.25c0-3.59 2.91-6.5 6.5-6.5s6.5 2.91 6.5 6.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
        </svg>
        @break
@endswitch
