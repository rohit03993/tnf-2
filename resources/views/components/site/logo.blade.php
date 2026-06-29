@props(['size' => 'md', 'logo' => null])

<a {{ $attributes->merge(['href' => '/', 'class' => 'tnf-header-logo']) }}>
    <x-site.brand-mark :size="$size" :logo="$logo" :show-wordmark="true" />
</a>
