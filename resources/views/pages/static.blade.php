<x-site.layout :title="$page->title" :seo="$seo">
    <div class="tnf-page-content">
        <article class="rounded-tnf-lg bg-white p-6 shadow-card prose max-w-none">
            <h1 class="tnf-section-title !border-0 !pl-0 mb-4">{{ $page->title }}</h1>
            {!! $page->content !!}
        </article>
    </div>
</x-site.layout>
