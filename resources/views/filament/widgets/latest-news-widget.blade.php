<x-filament-widgets::widget>
    <x-filament::section heading="Latest news">
        <div class="tnf-admin-card-list">
            @forelse ($this->getArticles() as $article)
                <a href="{{ $this->getEditUrl($article) }}" class="tnf-admin-card tnf-admin-card--news">
                    <div class="tnf-admin-card__media">
                        <img
                            src="{{ $article->featuredMedia?->url() ?? asset('images/admin-news-placeholder.svg') }}"
                            alt=""
                            loading="lazy"
                        >
                    </div>
                    <div class="tnf-admin-card__body">
                        <p class="tnf-admin-card__title">{{ $article->title }}</p>
                        <p class="tnf-admin-card__meta">
                            {{ $article->author?->name ?? 'Unknown' }}
                            ·
                            <span @class([
                                'tnf-admin-badge',
                                'tnf-admin-badge--published' => $article->status?->value === 'published',
                                'tnf-admin-badge--pending' => $article->status?->value === 'pending',
                                'tnf-admin-badge--draft' => $article->status?->value === 'draft',
                            ])>{{ ucfirst($article->status?->value ?? 'draft') }}</span>
                        </p>
                        <p class="tnf-admin-card__date">
                            {{ $article->published_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') ?? 'Not published' }}
                        </p>
                    </div>
                </a>
            @empty
                <p class="tnf-admin-empty">No news articles yet.</p>
            @endforelse
        </div>

        <div class="tnf-admin-view-all">
            <x-filament::button
                tag="a"
                :href="$this->getViewAllUrl()"
                color="gray"
                outlined
                class="w-full"
            >
                View all news
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
