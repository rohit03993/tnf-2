@props([
    'title',
    'description' => null,
    'breadcrumbs' => [],
])

<header {{ $attributes->merge(['class' => 'tnf-page-header']) }}>
    @if(count($breadcrumbs) > 0)
        <nav class="tnf-breadcrumbs" aria-label="Breadcrumb">
            <ol class="tnf-breadcrumbs-list">
                @foreach($breadcrumbs as $crumb)
                    <li class="tnf-breadcrumbs-item">
                        @if(! empty($crumb['url']) && ! $loop->last)
                            <a href="{{ $crumb['url'] }}" class="tnf-breadcrumbs-link">{{ $crumb['label'] }}</a>
                        @else
                            <span @class(['tnf-breadcrumbs-current' => $loop->last]) aria-current="{{ $loop->last ? 'page' : false }}">
                                {{ $crumb['label'] }}
                            </span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    @endif

    <h1 class="tnf-page-header-title">{{ $title }}</h1>

    @if(filled($description))
        <p class="tnf-page-header-desc">{{ $description }}</p>
    @endif
</header>
