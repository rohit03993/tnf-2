<x-site.layout title="ePaper — TNF Today">
    <div class="tnf-page-content">
        <h1 class="tnf-section-title mb-6">ePaper</h1>

        @if($editions->isNotEmpty())
            <div class="tnf-cat-block-grid tnf-cat-block-grid--epaper">
                @foreach($editions as $edition)
                    <x-cards.epaper-card :edition="$edition" />
                @endforeach
            </div>
        @else
            <div class="rounded-tnf-lg bg-white p-8 text-center shadow-card">
                <p class="text-tnf-muted">No ePaper editions published yet.</p>
                <p class="mt-2 text-tnf-sm text-tnf-muted">
                    Run <code class="rounded bg-tnf-gray px-1.5 py-0.5">php artisan db:seed --class=DemoContentSeeder</code> for demo editions.
                </p>
            </div>
        @endif
    </div>

    @php
        $needsPdfCovers = $editions->contains(
            fn ($edition) => ! $edition->coverImageUrl() && $edition->pdfPublicUrl(),
        );
    @endphp

    @if($needsPdfCovers)
        @push('scripts')
            <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
            @vite(['resources/js/epaper-covers.js'])
        @endpush
    @endif
</x-site.layout>
