@props(['tags'])

@if($tags->isNotEmpty())
<nav class="tnf-topic-pills lg:hidden" aria-label="Hot topics">
    @foreach($tags as $tag)
        <a href="{{ route('tag.show', $tag->slug) }}"
           @class([
               'tnf-topic-pill',
               'tnf-topic-pill--active' => request()->routeIs('tag.show') && request()->route('tag')?->slug === $tag->slug,
           ])>
            {{ $tag->name }}
        </a>
    @endforeach
</nav>
@endif
