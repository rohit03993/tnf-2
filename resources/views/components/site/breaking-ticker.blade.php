@props(['headlines'])

@if($headlines->isNotEmpty())
<div class="tnf-ticker-wrap">
    <div class="tnf-container">
        <div class="tnf-ticker" aria-label="Breaking news">
            <span class="tnf-ticker-label">Breaking News</span>
            <div class="tnf-ticker-track">
                <div class="tnf-ticker-content">
                    @foreach($headlines as $article)
                        <a href="{{ route('article.show', $article->slug) }}" class="tnf-ticker-item hover:underline">
                            {{ $article->title }}
                        </a>
                    @endforeach
                    {{-- Duplicate for seamless loop --}}
                    @foreach($headlines as $article)
                        <a href="{{ route('article.show', $article->slug) }}" class="tnf-ticker-item hover:underline">
                            {{ $article->title }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endif
