@props(['article'])

@php
    use App\Services\ArticleReadService;

    $readersCount = (int) ($article->readers_count ?? 0);
    $likesCount = (int) ($article->likes_count ?? 0);
    $isLiked = app(ArticleReadService::class)->readerHasLiked($article, request());
@endphp

<div
    id="tnf-article-engagement"
    class="tnf-article-engagement"
    data-read-url="{{ route('article.read', $article) }}"
    data-like-url="{{ route('article.like', $article) }}"
    aria-live="polite"
>
    <div class="tnf-article-engagement__inner">
        <button
            type="button"
            class="tnf-article-like {{ $isLiked ? 'tnf-article-like--active' : '' }}"
            data-article-like
            data-liked="{{ $isLiked ? 'true' : 'false' }}"
            aria-pressed="{{ $isLiked ? 'true' : 'false' }}"
            aria-label="{{ $isLiked ? 'Unlike this story' : 'Like this story' }}"
        >
            <span class="tnf-article-like__bubble" aria-hidden="true">
                <svg class="tnf-article-like__svg tnf-article-like__svg--outline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7.493 19.5h-.011m0 0a1.098 1.098 0 0 1-.668-.296 9.018 9.018 0 0 1-1.408-1.262 9.004 9.004 0 0 1-.859-5.032V7.5a1.125 1.125 0 0 1 1.125-1.125h2.25a1.125 1.125 0 0 1 1.125 1.125v4.002c0 .564.227 1.105.625 1.503.296.296.47.681.5 1.09m-5.011 0H7.5a1.125 1.125 0 0 1-1.125-1.125v-4.002A9.004 9.004 0 0 1 9.8 4.5h2.25c.864 0 1.655.357 2.222.934l4.596 4.596A1.125 1.125 0 0 1 18.75 11.25v2.25c0 .621-.504 1.125-1.125 1.125H15a1.125 1.125 0 0 0-1.125 1.125v1.5a1.125 1.125 0 0 1-1.125 1.125h-5.257Z" />
                </svg>
                <svg class="tnf-article-like__svg tnf-article-like__svg--filled" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M7.493 19.498h-.011m0 .011a10.015 10.015 0 0 1-.668-.296 9.004 9.004 0 0 1-.859-5.032V7.5a1.125 1.125 0 0 1 1.125-1.125h2.25a1.125 1.125 0 0 1 1.125 1.125v4.002c0 .564.227 1.105.625 1.503.296.296.47.681.5 1.09m-5.011 0H7.5a1.125 1.125 0 0 1-1.125-1.125v-4.002A9.004 9.004 0 0 1 9.8 4.5h2.25c.864 0 1.655.357 2.222.934l4.596 4.596a1.125 1.125 0 0 1 0 1.591l-4.596 4.596A2.952 2.952 0 0 1 7.493 19.498Z" />
                </svg>
            </span>
            <span class="tnf-article-like__copy">
                <span class="tnf-article-like__count" data-article-likes>{{ ArticleReadService::formatCount($likesCount) }}</span>
                <span class="tnf-article-like__label">{{ $isLiked ? 'Liked' : 'Like' }}</span>
            </span>
        </button>

        <span class="tnf-article-engagement__divider" aria-hidden="true"></span>

        <div class="tnf-article-stat">
            <span class="tnf-article-stat__bubble" aria-hidden="true">
                <svg class="tnf-article-stat__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.036 12.322a1 1 0 0 1 0-.644C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
            </span>
            <span class="tnf-article-stat__copy">
                <strong class="tnf-article-stat__count" data-article-readers>{{ ArticleReadService::formatCount($readersCount) }}</strong>
                <span class="tnf-article-stat__label" data-article-readers-label>{{ $readersCount === 1 ? 'Reader' : 'Readers' }}</span>
            </span>
        </div>
    </div>
</div>
