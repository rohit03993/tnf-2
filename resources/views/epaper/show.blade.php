<x-site.layout :epaper-viewer="true" :seo="$seo" :compact-chrome="true">
    @push('styles')
        @vite(['resources/css/epaper-viewer.css'])
    @endpush

    @if(! empty($config['pdfUrl']) && empty($config['pages']))
        @push('head')
            <link rel="preload" href="{{ $config['pdfUrl'] }}" as="fetch" type="application/pdf" crossorigin>
        @endpush
    @endif

    <div
        id="tnf-epaper-viewer"
        class="tnf-epaper-viewer{{ ! empty($config['clipMode']) ? ' tnf-epaper-viewer--shared-clip' : '' }}"
        data-config='@json($config)'
        aria-label="ePaper viewer"
    >
        @unless($config['clipMode'])
            <div class="tnf-ep-viewer-toolbar">
                <div class="tnf-container tnf-ep-toolbar-inner">
                    <div class="tnf-ep-toolbar-group tnf-ep-page-nav" role="navigation" aria-label="Pages">
                        <button type="button" class="tnf-ep-btn tnf-ep-page-nav__btn" data-ep-action="prev" aria-label="Previous page">‹</button>
                        <div class="tnf-ep-pager" data-ep-pager></div>
                        <button type="button" class="tnf-ep-btn tnf-ep-page-nav__btn" data-ep-action="next" aria-label="Next page">›</button>
                    </div>
                    <div class="tnf-ep-toolbar-group tnf-ep-toolbar-title">
                        <span class="tnf-ep-edition-title">{{ $edition->title }}</span>
                    </div>
                    <div class="tnf-ep-toolbar-group">
                        <button type="button" class="tnf-ep-btn" data-ep-action="zoom-out" aria-label="Zoom out">−</button>
                        <button type="button" class="tnf-ep-btn" data-ep-action="zoom-reset" aria-label="Fit">Fit</button>
                        <button type="button" class="tnf-ep-btn" data-ep-action="zoom-in" aria-label="Zoom in">+</button>
                        <button type="button" class="tnf-ep-btn tnf-ep-btn-share" data-ep-action="share" aria-label="Share edition">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                            </svg>
                            <span>Share</span>
                        </button>
                        <button type="button" class="tnf-ep-btn tnf-ep-btn-clip" data-ep-action="clip" aria-label="Clip and share">
                            <span class="tnf-ep-btn-clip-label">Clip</span>
                        </button>
                    </div>
                </div>
            </div>
        @else
            <div class="tnf-ep-clip-banner">
                <div class="tnf-container flex flex-wrap items-center justify-between gap-3 py-3">
                    <div class="min-w-0">
                        <p class="text-tnf-xs font-bold uppercase tracking-wider text-white/70">Shared newspaper clip</p>
                        <p class="truncate text-tnf-sm font-semibold text-white">{{ $edition->title }}</p>
                    </div>
                    <a href="{{ route('epaper.show', $edition->slug) }}" class="tnf-ep-full-edition-btn shrink-0">
                        Full edition
                    </a>
                </div>
            </div>
        @endunless

        <div class="tnf-ep-viewer-body">
            <aside class="tnf-ep-thumbs-sidebar" data-ep-thumbs-sidebar aria-label="Page thumbnails"></aside>

            <div class="tnf-ep-main">
                <div class="tnf-ep-thumbs-rail-wrap">
                    <div class="tnf-ep-thumbs-rail" data-ep-thumbs-rail aria-label="Page thumbnails"></div>
                </div>

                @unless($config['clipMode'])
                    @php
                        $epReaders = (int) ($edition->readers_count ?? 0);
                        $epLikes = (int) ($edition->likes_count ?? 0);
                        $epLiked = app(\App\Services\EpaperReadService::class)->readerHasLiked($edition, request());
                    @endphp
                    <div
                        class="tnf-ep-engagement"
                        data-ep-engagement
                        aria-live="polite"
                    >
                        <div class="tnf-ep-engagement-inner">
                            <div class="tnf-ep-engagement__left">
                                <button
                                    type="button"
                                    class="tnf-ep-like {{ $epLiked ? 'tnf-ep-like--active' : '' }}"
                                    data-ep-like
                                    data-liked="{{ $epLiked ? 'true' : 'false' }}"
                                    aria-pressed="{{ $epLiked ? 'true' : 'false' }}"
                                    aria-label="{{ $epLiked ? 'Unlike this edition' : 'Like this edition' }}"
                                >
                                    <span class="tnf-ep-like__icon" aria-hidden="true">
                                        <svg class="tnf-ep-like__svg tnf-ep-like__svg--outline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                                        </svg>
                                        <svg class="tnf-ep-like__svg tnf-ep-like__svg--filled" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z" />
                                        </svg>
                                    </span>
                                    <span class="tnf-ep-like__text">
                                        <span class="tnf-ep-like__label">{{ $epLiked ? 'Liked' : 'Like' }}</span>
                                        <span class="tnf-ep-like__count" data-ep-likes-count>{{ \App\Services\ArticleReadService::formatCount($epLikes) }}</span>
                                    </span>
                                </button>
                            </div>
                            <div class="tnf-ep-engagement__right">
                                <span class="tnf-ep-readers" data-ep-readers>
                                    <svg class="tnf-ep-readers__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M2.036 12.322a1 1 0 0 1 0-.644C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    <span class="tnf-ep-readers__text">
                                        <strong data-ep-readers-count>{{ \App\Services\ArticleReadService::formatCount($epReaders) }}</strong>
                                        <span data-ep-readers-label>{{ $epReaders === 1 ? 'reader' : 'readers' }}</span>
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>
                @endunless

                <div class="tnf-ep-stage-wrap" data-ep-stage-wrap>
                    <div class="tnf-ep-clip-bar hidden" data-ep-clip-bar>
                        <p class="tnf-ep-clip-bar-status" data-ep-clip-workspace-hint>
                            Adjust selection, then Share
                        </p>
                        <div class="tnf-ep-clip-presets" data-ep-clip-presets role="toolbar" aria-label="Clip options">
                            <button type="button" class="tnf-ep-clip-preset is-active" data-ep-clip-preset="lead">
                                Lead story
                            </button>
                            <button type="button" class="tnf-ep-clip-preset" data-ep-clip-preset="top">
                                Top half
                            </button>
                            <button type="button" class="tnf-ep-clip-preset" data-ep-clip-preset="full">
                                Full page
                            </button>
                        </div>
                    </div>

                    <div class="tnf-ep-clip-hint hidden" data-ep-clip-hint role="status">
                        Adjust selection, then Share
                    </div>

                    <div class="tnf-ep-stage" data-ep-stage>
                        <div class="tnf-ep-stage-spacer" data-ep-stage-spacer>
                            <div class="tnf-ep-stage-inner" data-ep-stage-inner>
                                <img data-ep-page-image alt="" class="tnf-ep-page-image" draggable="false">
                                <canvas data-ep-pdf-canvas class="tnf-ep-pdf-canvas hidden"></canvas>

                                <div class="tnf-ep-clip-screen hidden" data-ep-clip-screen aria-hidden="true">
                                    <div class="tnf-ep-clip-catcher is-draw-active" data-ep-clip-catcher></div>
                                    <div class="tnf-ep-clip-visual" data-ep-clip-visual>
                                        <div class="tnf-ep-clip-shade tnf-ep-clip-shade--top"></div>
                                        <div class="tnf-ep-clip-shade tnf-ep-clip-shade--left"></div>
                                        <div class="tnf-ep-clip-shade tnf-ep-clip-shade--right"></div>
                                        <div class="tnf-ep-clip-shade tnf-ep-clip-shade--bottom"></div>
                                        <div class="tnf-ep-clip-box">
                                            <div class="tnf-ep-clip-float-actions" data-ep-clip-float-actions>
                                                <button type="button" class="tnf-ep-clip-float-share" data-ep-clip-float-share disabled>
                                                    Share
                                                </button>
                                                <button type="button" class="tnf-ep-clip-float-cancel" data-ep-clip-float-cancel>
                                                    Cancel
                                                </button>
                                            </div>
                                            <span class="tnf-ep-clip-handle tnf-ep-clip-handle--tl" data-ep-clip-handle="tl"></span>
                                            <span class="tnf-ep-clip-handle tnf-ep-clip-handle--tr" data-ep-clip-handle="tr"></span>
                                            <span class="tnf-ep-clip-handle tnf-ep-clip-handle--bl" data-ep-clip-handle="bl"></span>
                                            <span class="tnf-ep-clip-handle tnf-ep-clip-handle--br" data-ep-clip-handle="br"></span>
                                            <span class="tnf-ep-clip-handle tnf-ep-clip-handle--t" data-ep-clip-handle="t"></span>
                                            <span class="tnf-ep-clip-handle tnf-ep-clip-handle--r" data-ep-clip-handle="r"></span>
                                            <span class="tnf-ep-clip-handle tnf-ep-clip-handle--b" data-ep-clip-handle="b"></span>
                                            <span class="tnf-ep-clip-handle tnf-ep-clip-handle--l" data-ep-clip-handle="l"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <nav class="tnf-ep-stage-nav" aria-label="Page navigation">
                        <button type="button" class="tnf-ep-stage-nav-btn tnf-ep-stage-nav-btn--prev" data-ep-action="prev" aria-label="Previous page">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        <button type="button" class="tnf-ep-stage-nav-btn tnf-ep-stage-nav-btn--next" data-ep-action="next" aria-label="Next page">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </nav>
                </div>
            </div>
        </div>

        @unless($config['clipMode'])
            <div class="tnf-ep-mobile-bar" data-ep-mobile-bar>
                <div class="tnf-container tnf-ep-mobile-bar-inner">
                    <button type="button" class="tnf-ep-mobile-icon-btn" data-ep-action="prev" aria-label="Previous page">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <span class="tnf-ep-mobile-page-wrap">
                        <button type="button" class="tnf-ep-mobile-page" data-ep-mobile-page aria-label="Choose page">1/1</button>
                        <select class="tnf-ep-mobile-page-select" data-ep-mobile-page-select aria-label="Page"></select>
                    </span>
                    <button type="button" class="tnf-ep-mobile-icon-btn" data-ep-action="next" aria-label="Next page">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                    <button type="button" class="tnf-ep-mobile-icon-btn tnf-ep-mobile-zoom-btn" data-ep-action="zoom-out" aria-label="Zoom out">−</button>
                    <button type="button" class="tnf-ep-mobile-icon-btn tnf-ep-mobile-zoom-btn" data-ep-action="zoom-in" aria-label="Zoom in">+</button>
                    <button type="button" class="tnf-ep-mobile-share-btn" data-ep-action="share" aria-label="Share">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                        </svg>
                    </button>
                    <button type="button" class="tnf-ep-mobile-clip-btn" data-ep-action="clip" aria-label="Clip">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h10v10H7zM3 3h4v4M17 3h4v4M3 17h4v4M17 17h4v4"/>
                        </svg>
                    </button>
                </div>
            </div>

            <p class="tnf-ep-mobile-zoom-hint lg:hidden" data-ep-mobile-zoom-hint hidden>
                Use − / + to zoom the page
            </p>
        @else
            <div class="tnf-container space-y-4 py-4">
                <div class="tnf-ep-clip-cta">
                    <div class="min-w-0 flex-1">
                        <p class="text-tnf-sm font-semibold text-tnf-navy">This is a shared clip from {{ $edition->title }}</p>
                        <p class="mt-1 text-tnf-xs text-tnf-muted">Read the complete digital edition with all pages.</p>
                    </div>
                    <div class="flex shrink-0 flex-wrap gap-2">
                        <button type="button" class="tnf-ep-full-edition-btn" data-ep-clip-page-download>
                            Download clip
                        </button>
                        <a href="{{ route('epaper.show', $edition->slug) }}" class="tnf-ep-full-edition-btn">
                            Read full edition
                        </a>
                    </div>
                </div>
                <x-site.share-bar :title="$edition->title" :url="request()->fullUrl()" />
            </div>
        @endunless

        <div class="tnf-ep-modal hidden" data-ep-share-modal role="dialog" aria-modal="true" aria-labelledby="tnf-ep-share-modal-title">
            <div class="tnf-ep-modal-backdrop" data-ep-share-modal-close></div>
            <div class="tnf-ep-modal-panel">
                <div class="tnf-ep-modal-grabber" aria-hidden="true"></div>
                <button type="button" class="tnf-ep-modal-close" data-ep-share-modal-close aria-label="Close">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                <div class="tnf-ep-modal-header">
                    <div class="tnf-ep-modal-icon" aria-hidden="true">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                        </svg>
                    </div>
                    <h2 id="tnf-ep-share-modal-title" class="tnf-ep-modal-title">Share newspaper</h2>
                    <p class="tnf-ep-modal-subtitle">Send the full digital edition or download the PDF.</p>
                </div>

                <label class="tnf-ep-clip-url-label" for="tnf-ep-share-url-input">Edition link</label>
                <div class="tnf-ep-clip-url-row">
                    <input
                        id="tnf-ep-share-url-input"
                        type="text"
                        readonly
                        class="tnf-ep-clip-url"
                        data-ep-share-url
                        value="{{ $config['shareUrl'] }}"
                    >
                    <button type="button" class="tnf-ep-clip-copy-btn" data-ep-copy-share>
                        <svg class="tnf-ep-clip-copy-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        <svg class="tnf-ep-clip-copied-icon hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="tnf-ep-clip-copy-text">Copy</span>
                    </button>
                </div>

                <p class="tnf-ep-share-label">Share via</p>
                <div class="tnf-ep-clip-share" data-ep-edition-share>
                    <a class="tnf-ep-share-btn tnf-ep-share-btn--facebook" data-ep-share="facebook" href="#" target="_blank" rel="noopener" aria-label="Share on Facebook">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12S0 5.446 0 12.073c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    <a class="tnf-ep-share-btn tnf-ep-share-btn--whatsapp" data-ep-share="whatsapp" href="#" target="_blank" rel="noopener" aria-label="Share on WhatsApp">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.984.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.884 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    </a>
                    <a class="tnf-ep-share-btn tnf-ep-share-btn--linkedin" data-ep-share="linkedin" href="#" target="_blank" rel="noopener" aria-label="Share on LinkedIn">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 114.126 0 2.065 2.065 0 01-2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    </a>
                    <a class="tnf-ep-share-btn tnf-ep-share-btn--x" data-ep-share="x" href="#" target="_blank" rel="noopener" aria-label="Share on X">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <a class="tnf-ep-share-btn tnf-ep-share-btn--email" data-ep-share="email" href="#" target="_blank" rel="noopener" aria-label="Share by email">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                    </a>
                </div>

                <div class="tnf-ep-clip-modal-actions">
                    @if(! empty($config['pdfDownloadUrl']))
                        <a class="tnf-ep-clip-modal-action" data-ep-download-pdf href="{{ $config['pdfDownloadUrl'] }}" download>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Download PDF
                        </a>
                    @endif
                    <a class="tnf-ep-clip-modal-action" data-ep-share-open href="{{ $config['shareUrl'] }}" target="_blank" rel="noopener">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        Open edition
                    </a>
                </div>

                <button type="button" class="tnf-ep-clip-native hidden" data-ep-share-native>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Share from device
                </button>
            </div>
        </div>

        <div class="tnf-ep-modal hidden" data-ep-clip-modal role="dialog" aria-modal="true" aria-labelledby="tnf-ep-clip-modal-title">
            <div class="tnf-ep-modal-backdrop" data-ep-modal-close></div>
            <div class="tnf-ep-modal-panel">
                <div class="tnf-ep-modal-grabber" aria-hidden="true"></div>
                <button type="button" class="tnf-ep-modal-close" data-ep-modal-close aria-label="Close">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                <div class="tnf-ep-modal-header">
                    <div class="tnf-ep-modal-icon" aria-hidden="true">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                        </svg>
                    </div>
                    <h2 id="tnf-ep-clip-modal-title" class="tnf-ep-modal-title">Share clip</h2>
                    <p class="tnf-ep-modal-subtitle">Send your selected section or save the link.</p>
                </div>

                <div class="tnf-ep-clip-preview-wrap" data-ep-clip-preview-wrap>
                    <p class="tnf-ep-clip-preview-label">Selected section</p>
                    <div class="tnf-ep-clip-preview-frame" data-ep-clip-preview-frame>
                        <img src="" alt="Selected newspaper clip preview" class="tnf-ep-clip-preview" data-ep-clip-preview>
                    </div>
                </div>

                <label class="tnf-ep-clip-url-label" for="tnf-ep-clip-url-input">Clip link</label>
                <div class="tnf-ep-clip-url-row">
                    <input
                        id="tnf-ep-clip-url-input"
                        type="text"
                        readonly
                        class="tnf-ep-clip-url"
                        data-ep-clip-url
                    >
                    <button type="button" class="tnf-ep-clip-copy-btn" data-ep-copy-clip>
                        <svg class="tnf-ep-clip-copy-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        <svg class="tnf-ep-clip-copied-icon hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="tnf-ep-clip-copy-text">Copy</span>
                    </button>
                </div>

                <p class="tnf-ep-share-label">Share via</p>
                <div class="tnf-ep-clip-share" data-ep-clip-share>
                    <a class="tnf-ep-share-btn tnf-ep-share-btn--facebook" data-ep-share="facebook" href="#" target="_blank" rel="noopener" aria-label="Share on Facebook">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12S0 5.446 0 12.073c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    <a class="tnf-ep-share-btn tnf-ep-share-btn--whatsapp" data-ep-share="whatsapp" href="#" target="_blank" rel="noopener" aria-label="Share on WhatsApp">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.984.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.884 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    </a>
                    <a class="tnf-ep-share-btn tnf-ep-share-btn--linkedin" data-ep-share="linkedin" href="#" target="_blank" rel="noopener" aria-label="Share on LinkedIn">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 114.126 0 2.065 2.065 0 01-2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    </a>
                    <a class="tnf-ep-share-btn tnf-ep-share-btn--x" data-ep-share="x" href="#" target="_blank" rel="noopener" aria-label="Share on X">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <a class="tnf-ep-share-btn tnf-ep-share-btn--email" data-ep-share="email" href="#" target="_blank" rel="noopener" aria-label="Share by email">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                    </a>
                </div>

                <div class="tnf-ep-clip-modal-actions">
                    <a class="tnf-ep-clip-modal-action" data-ep-clip-open href="#" target="_blank" rel="noopener">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        Open
                    </a>
                    <button type="button" class="tnf-ep-clip-modal-action" data-ep-clip-download>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download
                    </button>
                </div>

                <button type="button" class="tnf-ep-clip-native hidden" data-ep-clip-native>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Share from device
                </button>
            </div>
        </div>

    </div>

    @push('scripts')
        @if(! empty($config['pdfUrl']) || ($config['clipMode'] ?? false))
            <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
        @endif
        @vite(['resources/js/epaper-viewer.js'])
    @endpush
</x-site.layout>
