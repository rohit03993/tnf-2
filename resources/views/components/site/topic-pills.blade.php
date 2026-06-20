@props(['tags'])

@if($tags->isNotEmpty())
<nav class="tnf-topic-pills bg-white border-b border-tnf-gray-dark" aria-label="Hot topics">
    @foreach($tags as $tag)
        <a href="{{ route('tag.show', $tag->slug) }}" class="tnf-topic-pill">
            {{ $tag->name }}
        </a>
    @endforeach
</nav>
@endif
