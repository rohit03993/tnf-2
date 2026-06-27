<x-site.layout title="Videos — TNF Today">
    <div class="tnf-page-content">
        <x-site.page-header
            title="Videos"
            description="Watch the latest news videos from TNF Today"
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'Videos', 'url' => null],
            ]"
            class="mb-6"
        />

        @if($videos->count() > 0)
            <div class="tnf-archive-grid tnf-archive-grid--videos">
                @foreach($videos as $video)
                    <x-cards.video-card :video="$video" />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $videos->links() }}
            </div>
        @else
            <div class="tnf-empty-state">
                <p class="tnf-empty-state-title">No videos yet</p>
                <p class="tnf-empty-state-desc">No videos published yet.</p>
            </div>
        @endif
    </div>
</x-site.layout>
