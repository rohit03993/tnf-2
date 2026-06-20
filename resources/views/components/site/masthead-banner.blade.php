@props(['image', 'url'])

@if(filled($image))
    @if(filled($url))
        @php
            $bannerHost = parse_url($url, PHP_URL_HOST);
            $isExternalBanner = $bannerHost && $bannerHost !== request()->getHost();
        @endphp
        <a
            href="{{ $url }}"
            class="tnf-masthead-banner"
            @if($isExternalBanner) data-tnf-external="1" @endif
        >
            <x-site.media-frame
                variant="hero"
                :image-url="asset('storage/'.$image)"
                alt="Promo banner"
            />
        </a>
    @else
        <div class="tnf-masthead-banner">
            <x-site.media-frame
                variant="hero"
                :image-url="asset('storage/'.$image)"
                alt="Promo banner"
            />
        </div>
    @endif
@endif
