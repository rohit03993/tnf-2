<x-site.layout title="ePaper — TNF Today">
    <div class="tnf-page-content">
        <x-site.page-header
            title="ePaper"
            description="Browse digital newspaper editions"
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'ePaper', 'url' => null],
            ]"
            class="mb-6"
        />

        @if($editions->isNotEmpty())
            <div class="tnf-archive-grid tnf-archive-grid--epaper">
                @foreach($editions as $edition)
                    <x-cards.epaper-card :edition="$edition" />
                @endforeach
            </div>
        @else
            <div class="tnf-empty-state">
                <p class="tnf-empty-state-title">No editions yet</p>
                <p class="tnf-empty-state-desc">No ePaper editions published yet.</p>
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
