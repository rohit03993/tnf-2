@props(['user', 'publishedAt' => null])

@if($user)
    <div {{ $attributes->merge(['class' => 'tnf-article-byline']) }}>
        @if($user->avatarUrl())
            <img
                src="{{ $user->avatarUrl() }}"
                alt="{{ $user->name }}"
                class="tnf-article-byline-avatar"
                loading="lazy"
            >
        @else
            <span class="tnf-article-byline-avatar tnf-article-byline-avatar--initials" aria-hidden="true">
                {{ $user->initials() }}
            </span>
        @endif

        <div class="tnf-article-byline-text">
            <p class="tnf-article-byline-name">{{ $user->name }}</p>
            @if($publishedAt)
                <time class="tnf-article-byline-date" datetime="{{ \App\Support\NewsDate::iso($publishedAt) }}">
                    {{ \App\Support\NewsDate::formatDateTime($publishedAt) }}
                </time>
            @endif
        </div>
    </div>
@endif
