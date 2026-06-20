<x-site.layout title="Videos — TNF Today">
    <div class="tnf-page-content">
        <h1 class="tnf-section-title mb-6">Videos</h1>

        @if($videos->count() > 0)
            <div class="tnf-video-grid">
                @foreach($videos as $video)
                    <x-cards.video-card :video="$video" />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $videos->links() }}
            </div>
        @else
            <div class="rounded-tnf-lg bg-white p-8 text-center shadow-card">
                <p class="text-tnf-muted">No videos published yet.</p>
                <p class="mt-2 text-tnf-sm text-tnf-muted">
                    Run <code class="rounded bg-tnf-gray px-1.5 py-0.5">php artisan db:seed --class=DemoContentSeeder</code> for demo videos.
                </p>
            </div>
        @endif
    </div>
</x-site.layout>
