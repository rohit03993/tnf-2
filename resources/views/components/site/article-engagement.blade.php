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
    <div class="tnf-article-engagement__left">
        <button
            type="button"
            class="tnf-article-like {{ $isLiked ? 'tnf-article-like--active' : '' }}"
            data-article-like
            data-liked="{{ $isLiked ? 'true' : 'false' }}"
            aria-pressed="{{ $isLiked ? 'true' : 'false' }}"
            aria-label="{{ $isLiked ? 'Unlike this story' : 'Like this story' }}"
        >
            <span class="tnf-article-like__icon" aria-hidden="true">
                <svg class="tnf-article-like__svg tnf-article-like__svg--outline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                </svg>
                <svg class="tnf-article-like__svg tnf-article-like__svg--filled" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z" />
                </svg>
            </span>
            <span class="tnf-article-like__text">
                <span class="tnf-article-like__label">{{ $isLiked ? 'Liked' : 'Like' }}</span>
                <span class="tnf-article-like__count" data-article-likes>{{ ArticleReadService::formatCount($likesCount) }}</span>
            </span>
        </button>
    </div>

    <div class="tnf-article-engagement__right">
        <span class="tnf-article-stat">
            <svg class="tnf-article-stat__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M2.036 12.322a1 1 0 0 1 0-.644C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            </svg>
            <span class="tnf-article-stat__text">
                <strong data-article-readers>{{ ArticleReadService::formatCount($readersCount) }}</strong>
                <span data-article-readers-label>{{ $readersCount === 1 ? 'reader' : 'readers' }}</span>
            </span>
        </span>
    </div>
</div>
