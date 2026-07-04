<x-filament-widgets::widget>
    <x-filament::section heading="Reporter performance">
        <div class="tnf-admin-card-list tnf-admin-card-list--team">
            @forelse ($this->getReporters() as $reporter)
                <div class="tnf-admin-card tnf-admin-card--team">
                    <div class="tnf-admin-card__body">
                        <p class="tnf-admin-card__title">{{ $reporter->name }}</p>
                        <div class="tnf-admin-team-stats">
                            <span class="tnf-admin-team-stat">
                                <strong>{{ $reporter->published_articles_count }}</strong>
                                published
                            </span>
                            <span class="tnf-admin-team-stat tnf-admin-team-stat--pending">
                                <strong>{{ $reporter->pending_articles_count }}</strong>
                                pending
                            </span>
                            <span class="tnf-admin-team-stat">
                                <strong>{{ $reporter->published_videos_count }}</strong>
                                videos
                            </span>
                        </div>
                    </div>
                </div>
            @empty
                <p class="tnf-admin-empty">No reporters yet.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
