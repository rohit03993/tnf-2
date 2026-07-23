/**
 * TNF Today — ePaper viewer (Phase I)
 */

class TnfEpaperViewer {
    constructor(root, config) {
        this.root = root;
        this.config = config;
        this.currentPage = config.initialPage || 1;
        this.fitZoom = 1;
        this.userZoomFactor = 1;
        this.pageWidth = 0;
        this.pageHeight = 0;
        this.clipMode = false;
        this.clipWorkspaceActive = false;
        this.clipWorkspaceImageDataUrl = '';
        this.clipCaptureSource = null;
        this.clipMasterScale = 1;
        this.clipPageZoom = 1;
        this.clipPageBaseWidth = 0;
        this.lastClipPointerId = null;
        this.clipDrawMode = false;
        this.cachedPdfRenderScale = 0;
        this.pdfZoomRenderTimer = null;
        this.clipInstructionTimer = null;
        this.clipStart = null;
        this.clipRect = null;
        this.pdfDoc = null;

        this.els = {
            stageWrap: root.querySelector('[data-ep-stage-wrap]'),
            stage: root.querySelector('[data-ep-stage]'),
            stageSpacer: root.querySelector('[data-ep-stage-spacer]'),
            stageInner: root.querySelector('[data-ep-stage-inner]'),
            pageImage: root.querySelector('[data-ep-page-image]'),
            pdfCanvas: root.querySelector('[data-ep-pdf-canvas]'),
            pageSelect: root.querySelector('[data-ep-page-select]'),
            pager: root.querySelector('[data-ep-pager]'),
            thumbsSidebar: root.querySelector('[data-ep-thumbs-sidebar]'),
            thumbsRail: root.querySelector('[data-ep-thumbs-rail]'),
            mobilePage: root.querySelector('[data-ep-mobile-page]'),
            mobilePageSelect: root.querySelector('[data-ep-mobile-page-select]'),
            clipModal: root.querySelector('[data-ep-clip-modal]'),
            clipUrl: root.querySelector('[data-ep-clip-url]'),
            clipShare: root.querySelector('[data-ep-clip-share]'),
            clipCopyBtn: root.querySelector('[data-ep-copy-clip]'),
            clipNativeBtn: root.querySelector('[data-ep-clip-native]'),
            clipOpen: root.querySelector('[data-ep-clip-open]'),
            clipDownload: root.querySelector('[data-ep-clip-download]'),
            clipHint: root.querySelector('[data-ep-clip-hint]'),
            clipPreview: root.querySelector('[data-ep-clip-preview]'),
            clipPreviewFrame: root.querySelector('[data-ep-clip-preview-frame]'),
            clipPreviewWrap: root.querySelector('[data-ep-clip-preview-wrap]'),
            clipBar: root.querySelector('[data-ep-clip-bar]'),
            clipScreen: root.querySelector('[data-ep-clip-screen]'),
            clipWorkspaceHint: root.querySelector('[data-ep-clip-workspace-hint]'),
            clipPresets: root.querySelector('[data-ep-clip-presets]'),
            clipFloatShare: root.querySelector('[data-ep-clip-float-share]'),
            clipFloatCancel: root.querySelector('[data-ep-clip-float-cancel]'),
            shareModal: root.querySelector('[data-ep-share-modal]'),
            shareUrl: root.querySelector('[data-ep-share-url]'),
            editionShare: root.querySelector('[data-ep-edition-share]'),
            shareCopyBtn: root.querySelector('[data-ep-copy-share]'),
            shareNativeBtn: root.querySelector('[data-ep-share-native]'),
            shareOpen: root.querySelector('[data-ep-share-open]'),
        };

        this.activeClipUrl = '';
        this.clipPreviewDataUrl = '';
        this.pendingClip = null;
        this.clipNormalized = null;
        this.clipDragCleanup = null;
        this.clipScrollHandler = null;
        this.stagePanCleanup = null;
        this.resizeHandler = null;
        this.pdfThumbCache = {};
        this.activePointerId = null;
        this.pinchStartDistance = 0;
        this.pinchStartZoom = 1;
        this.lastTapAt = 0;
        this.livePreviewFrame = null;
        this.clipShareBusy = false;
        this.readRecorded = false;
        this.liking = false;
        this.els.engagement = root.querySelector('[data-ep-engagement]');
        this.els.likeBtn = root.querySelector('[data-ep-like]');
        this.els.likesCount = root.querySelector('[data-ep-likes-count]');
        this.els.likeLabel = root.querySelector('.tnf-ep-like__label');
    }

    get effectiveZoom() {
        return this.fitZoom * this.userZoomFactor;
    }

    async init() {
        if (this.config.clipMode && this.config.clip) {
            this.root.classList.add('tnf-epaper-viewer--shared-clip');
            this.setPdfLoading(true);

            if (! this.isValidClip(this.config.clip)) {
                this.showEmptyState('pdf');
                this.setPdfLoading(false);

                return;
            }

            await this.renderClipOnly();
            this.setPdfLoading(false);

            return;
        }

        if (! this.config.pages.length && this.config.pdfUrl) {
            this.setPdfLoading(true);
            await this.initPdfFallback();
        }

        if (! this.config.pages.length && ! this.pdfDoc) {
            this.showEmptyState(this.config.pdfUrl ? 'pdf' : 'empty');
            return;
        }

        this.buildThumbnails();
        this.buildPageSelect();
        this.bindActions();
        this.bindEngagement();
        this.bindTouchZoom();
        this.bindResizeHandler();
        this.bindMobilePanScroll();
        this.bindMobilePageSwipe();
        this.syncChromeHeights();
        this.maybeShowZoomHint();
        await this.setPage(this.currentPage, false);
        this.setPdfLoading(false);
        this.scheduleReadTracking();

        if (this.pdfDoc) {
            this.schedulePdfThumbnailPrefetch();
        }
    }

    bindEngagement() {
        this.els.likeBtn?.addEventListener('click', () => this.toggleLike());
    }

    scheduleReadTracking() {
        if (this.config.readRecorded) {
            this.readRecorded = true;

            return;
        }

        if (! this.config.readUrl || this.readRecorded) {
            return;
        }

        window.setTimeout(() => this.recordRead(), 1200);
    }

    readCookie(name) {
        const escaped = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const match = document.cookie.match(new RegExp(`(?:^|; )${escaped}=([^;]*)`));

        return match ? decodeURIComponent(match[1]) : null;
    }

    buildJsonHeaders() {
        const headers = {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };

        const xsrfToken = this.readCookie('XSRF-TOKEN');

        if (xsrfToken) {
            headers['X-XSRF-TOKEN'] = xsrfToken;
        } else {
            const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            if (metaToken) {
                headers['X-CSRF-TOKEN'] = metaToken;
            }
        }

        return headers;
    }

    parseCount(value) {
        const parsed = parseInt(String(value ?? '0').replace(/,/g, ''), 10);

        return Number.isFinite(parsed) ? parsed : 0;
    }

    postEngagement(url) {
        return fetch(url, {
            method: 'POST',
            headers: this.buildJsonHeaders(),
            credentials: 'same-origin',
        }).then(async (response) => {
            if (! response.ok) {
                throw new Error(`Request failed (${response.status})`);
            }

            return response.json();
        });
    }

    updateEngagement(payload) {
        if (! payload) {
            return;
        }

        if (typeof payload.readers_count === 'number') {
            const label = payload.readers_label ?? String(payload.readers_count);

            this.root.querySelectorAll('[data-ep-readers-count]').forEach((el) => {
                el.textContent = label;
            });

            this.root.querySelectorAll('[data-ep-readers-label]').forEach((el) => {
                el.textContent = payload.readers_count === 1 ? 'reader' : 'readers';
            });
        }

        if (typeof payload.likes_count === 'number' && this.els.likesCount) {
            this.els.likesCount.textContent = payload.likes_label ?? String(payload.likes_count);
        }

        if (typeof payload.liked === 'boolean' && this.els.likeBtn) {
            this.els.likeBtn.dataset.liked = payload.liked ? 'true' : 'false';
            this.els.likeBtn.setAttribute('aria-pressed', payload.liked ? 'true' : 'false');
            this.els.likeBtn.setAttribute('aria-label', payload.liked ? 'Unlike this edition' : 'Like this edition');
            this.els.likeBtn.classList.toggle('tnf-ep-like--active', payload.liked);

            if (this.els.likeLabel) {
                this.els.likeLabel.textContent = payload.liked ? 'Liked' : 'Like';
            }
        }
    }

    recordRead() {
        if (this.readRecorded || ! this.config.readUrl) {
            return;
        }

        this.readRecorded = true;

        this.postEngagement(this.config.readUrl)
            .then((payload) => this.updateEngagement(payload))
            .catch(() => {
                this.readRecorded = false;
            });
    }

    toggleLike() {
        if (this.liking || ! this.config.likeUrl || ! this.els.likeBtn) {
            return;
        }

        const wasLiked = this.els.likeBtn.dataset.liked === 'true';
        const previousCount = this.parseCount(this.els.likesCount?.textContent);
        const optimisticCount = wasLiked
            ? Math.max(0, previousCount - 1)
            : previousCount + 1;

        this.liking = true;
        this.els.likeBtn.classList.add('tnf-ep-like--busy');

        this.updateEngagement({
            liked: ! wasLiked,
            likes_count: optimisticCount,
            likes_label: String(optimisticCount),
        });

        this.postEngagement(this.config.likeUrl)
            .then((payload) => {
                this.updateEngagement(payload);

                if (! this.readRecorded && this.config.readUrl) {
                    this.readRecorded = true;
                }
            })
            .catch(() => {
                this.updateEngagement({
                    liked: wasLiked,
                    likes_count: previousCount,
                    likes_label: String(previousCount),
                });
            })
            .finally(() => {
                this.liking = false;
                this.els.likeBtn?.classList.remove('tnf-ep-like--busy');
            });
    }

    setPdfLoading(isLoading) {
        this.root.classList.toggle('is-pdf-loading', isLoading);
    }

    schedulePdfThumbnailPrefetch() {
        const run = () => void this.prefetchPdfThumbnails();

        if (typeof requestIdleCallback === 'function') {
            requestIdleCallback(run, { timeout: 4000 });
        } else {
            window.setTimeout(run, 800);
        }
    }

    get pageCount() {
        return this.config.pageCount || this.config.pages.length || (this.pdfDoc ? this.pdfDoc.numPages : 0);
    }

    isCoarsePointer() {
        return window.matchMedia('(pointer: coarse)').matches || 'ontouchstart' in window;
    }

    /**
     * Drag-to-scroll on the PDF stage (touch + mouse).
     * Uses a movement threshold so taps/clicks still work; disabled in clip mode.
     */
    bindStageDragScroll(element, { isEnabled = () => true } = {}) {
        if (! element) {
            return null;
        }

        const dragThreshold = 8;
        const interactiveSelector = [
            '[data-ep-clip-move]',
            '[data-ep-clip-handle]',
            '.tnf-ep-clip-preset',
            '.tnf-ep-clip-workspace-footer button',
            '.tnf-ep-clip-workspace-header button',
            '.tnf-ep-clip-workspace-cancel',
            '.tnf-ep-clip-workspace-share',
            '.tnf-ep-clip-workspace-whatsapp',
            '.tnf-ep-mobile-icon-btn',
            '.tnf-ep-mobile-clip-btn',
            '.tnf-ep-mobile-share-btn',
            '.tnf-ep-mobile-zoom-btn',
            '[data-ep-clip-zoom-in]',
            '[data-ep-clip-zoom-out]',
            '.tnf-ep-clip-zoom-btn',
            '.tnf-ep-stage-nav-btn',
            'button',
            'a',
            'input',
            'select',
            'textarea',
        ].join(', ');

        let pointerId = null;
        let panning = false;
        let startX = 0;
        let startY = 0;
        let originScrollX = 0;
        let originScrollY = 0;
        let usesStageScroll = false;

        const readScrollTarget = () => {
            const overflowY = getComputedStyle(element).overflowY;
            usesStageScroll = overflowY === 'auto' || overflowY === 'scroll';

            if (usesStageScroll) {
                return { x: element.scrollLeft, y: element.scrollTop };
            }

            return { x: window.scrollX, y: window.scrollY };
        };

        const applyScroll = (x, y) => {
            if (usesStageScroll) {
                element.scrollLeft = x;
                element.scrollTop = y;

                return;
            }

            window.scrollTo(x, y);
        };

        const endPan = (event) => {
            if (pointerId === null || (event && event.pointerId !== pointerId)) {
                return;
            }

            if (panning) {
                try {
                    element.releasePointerCapture(pointerId);
                } catch (error) {
                    // Pointer may already be released.
                }

                element.classList.remove('is-panning');
            }

            pointerId = null;
            panning = false;
        };

        const onPointerDown = (event) => {
            if (! isEnabled() || ! event.isPrimary || event.button !== 0) {
                return;
            }

            if (event.target instanceof Element && event.target.closest(interactiveSelector)) {
                return;
            }

            const scroll = readScrollTarget();

            pointerId = event.pointerId;
            panning = false;
            startX = event.clientX;
            startY = event.clientY;
            originScrollX = scroll.x;
            originScrollY = scroll.y;
        };

        const onPointerMove = (event) => {
            if (pointerId === null || event.pointerId !== pointerId || ! isEnabled()) {
                return;
            }

            const dx = startX - event.clientX;
            const dy = startY - event.clientY;

            if (! panning) {
                if (Math.hypot(dx, dy) < dragThreshold) {
                    return;
                }

                panning = true;
                element.classList.add('is-panning');

                try {
                    element.setPointerCapture(pointerId);
                } catch (error) {
                    // Capture is best-effort; scrolling still works without it.
                }
            }

            event.preventDefault();
            applyScroll(originScrollX + dx, originScrollY + dy);
        };

        element.classList.add('tnf-ep-stage--drag-scroll');
        element.addEventListener('pointerdown', onPointerDown);
        element.addEventListener('pointermove', onPointerMove, { passive: false });
        element.addEventListener('pointerup', endPan);
        element.addEventListener('pointercancel', endPan);
        element.addEventListener('lostpointercapture', endPan);

        return () => {
            endPan();
            element.classList.remove('tnf-ep-stage--drag-scroll', 'is-panning');
            element.removeEventListener('pointerdown', onPointerDown);
            element.removeEventListener('pointermove', onPointerMove);
            element.removeEventListener('pointerup', endPan);
            element.removeEventListener('pointercancel', endPan);
            element.removeEventListener('lostpointercapture', endPan);
        };
    }

    bindMobilePanScroll() {
        if (this.stagePanCleanup) {
            this.stagePanCleanup();
            this.stagePanCleanup = null;
        }

        const stage = this.els.stage;

        if (! stage) {
            return;
        }

        // Touch devices: native overflow scroll (inertia + compositor). JS pan feels laggy.
        if (this.isCoarsePointer()) {
            stage.classList.remove('tnf-ep-stage--drag-scroll', 'is-panning');
            stage.classList.add('tnf-ep-stage--native-scroll');

            return;
        }

        stage.classList.remove('tnf-ep-stage--native-scroll');
        this.stagePanCleanup = this.bindStageDragScroll(stage, {
            isEnabled: () => ! this.clipMode && ! this.config.clipMode,
        });
    }

    bindMobilePageSwipe() {
        const stage = this.els.stage;

        if (! stage || ! this.isCoarsePointer() || this.mobileSwipeBound) {
            return;
        }

        this.mobileSwipeBound = true;
        let startX = 0;
        let startY = 0;
        let tracking = false;

        stage.addEventListener('touchstart', (event) => {
            if (this.clipMode || event.touches.length !== 1) {
                tracking = false;
                return;
            }

            startX = event.touches[0].clientX;
            startY = event.touches[0].clientY;
            tracking = true;
        }, { passive: true });

        stage.addEventListener('touchend', (event) => {
            if (! tracking || this.clipMode || this.userZoomFactor > 1.05) {
                tracking = false;
                return;
            }

            const touch = event.changedTouches[0];
            const dx = touch.clientX - startX;
            const dy = touch.clientY - startY;
            tracking = false;

            if (Math.abs(dx) < 72 || Math.abs(dx) < Math.abs(dy) * 1.35) {
                return;
            }

            if (dx < 0) {
                this.setPage(this.currentPage + 1);
            } else {
                this.setPage(this.currentPage - 1);
            }
        }, { passive: true });
    }

    bindClipWorkspacePanScroll() {
        // In-page clip no longer uses a separate scrollable workspace.
        if (this.clipPanCleanup) {
            this.clipPanCleanup();
            this.clipPanCleanup = null;
        }
    }

    clipHintText() {
        return 'Drag on the page to select a section';
    }

    getPageUrl(page) {
        const item = this.config.pages[page - 1];
        return item?.url || null;
    }

    buildThumbnails() {
        [this.els.thumbsSidebar, this.els.thumbsRail].forEach((container) => {
            if (! container) {
                return;
            }

            container.innerHTML = '';

            for (let page = 1; page <= this.pageCount; page++) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'tnf-ep-thumb';
                btn.dataset.page = String(page);
                btn.setAttribute('aria-label', `Page ${page}`);

                const img = document.createElement('img');
                img.alt = `Page ${page}`;
                img.loading = 'lazy';
                const thumbSrc = this.getPageUrl(page) || this.pdfThumbCache[page] || null;

                if (thumbSrc) {
                    img.src = thumbSrc;
                } else {
                    img.classList.add('tnf-ep-thumb-placeholder');
                }

                btn.appendChild(img);

                const label = document.createElement('span');
                label.className = 'tnf-ep-thumb-label';
                label.textContent = String(page);
                btn.appendChild(label);

                btn.addEventListener('click', () => this.setPage(page));
                container.appendChild(btn);
            }
        });
    }

    buildPageSelect() {
        const selects = [this.els.pageSelect, this.els.mobilePageSelect].filter(Boolean);

        if (! selects.length) {
            return;
        }

        selects.forEach((select) => {
            select.innerHTML = '';

            for (let page = 1; page <= this.pageCount; page++) {
                const option = document.createElement('option');
                option.value = String(page);
                option.textContent = `${page} / ${this.pageCount}`;
                select.appendChild(option);
            }

            select.addEventListener('change', () => {
                this.setPage(Number(select.value));
            });
        });
    }

    pageLabel(page = this.currentPage) {
        return `${page}/${Math.max(1, this.pageCount)}`;
    }

    pagerPages() {
        const total = Math.max(1, this.pageCount);
        const current = this.currentPage;

        if (total <= 10) {
            return Array.from({ length: total }, (_, index) => index + 1);
        }

        const pages = new Set([1, total, current]);
        for (let page = current - 1; page <= current + 1; page++) {
            if (page >= 1 && page <= total) {
                pages.add(page);
            }
        }

        const sorted = [...pages].sort((a, b) => a - b);
        const result = [];

        sorted.forEach((page, index) => {
            if (index > 0 && page - sorted[index - 1] > 1) {
                result.push('…');
            }
            result.push(page);
        });

        return result;
    }

    syncChromeHeights() {
        const root = document.documentElement;
        const mobileBar = this.root.querySelector('[data-ep-mobile-bar]');
        const engagement = this.root.querySelector('[data-ep-engagement]');
        const thumbs = this.root.querySelector('[data-ep-thumbs-rail-wrap]');
        const clipBar = this.root.querySelector('[data-ep-clip-bar]');
        const clipDock = this.root.querySelector('[data-ep-clip-mobile-dock]');

        if (mobileBar) {
            root.style.setProperty('--tnf-ep-mobile-bar', `${mobileBar.offsetHeight}px`);
        }

        if (engagement && getComputedStyle(engagement).display !== 'none') {
            root.style.setProperty('--tnf-ep-engagement-h', `${engagement.offsetHeight}px`);
        } else {
            root.style.setProperty('--tnf-ep-engagement-h', '0px');
        }

        if (thumbs && getComputedStyle(thumbs).display !== 'none') {
            root.style.setProperty('--tnf-ep-thumbs-h', `${thumbs.offsetHeight}px`);
        } else {
            root.style.setProperty('--tnf-ep-thumbs-h', '0px');
        }

        const clipBarH = clipBar && ! clipBar.classList.contains('hidden') ? clipBar.offsetHeight : 0;
        const clipDockH = clipDock && ! clipDock.classList.contains('hidden') ? clipDock.offsetHeight : 0;

        root.style.setProperty('--tnf-ep-clip-bar', `${clipBarH}px`);
        root.style.setProperty('--tnf-ep-clip-dock', `${clipDockH}px`);
        root.style.setProperty('--tnf-ep-clip-chrome', `${Math.max(clipBarH + clipDockH, 24)}px`);
    }

    maybeShowZoomHint() {
        const hint = this.root.querySelector('[data-ep-mobile-zoom-hint]');

        if (! hint || window.matchMedia('(min-width: 1024px)').matches) {
            return;
        }

        try {
            if (window.localStorage.getItem('tnf_ep_zoom_hint_seen') === '1') {
                return;
            }
        } catch {
            return;
        }

        hint.hidden = false;

        window.setTimeout(() => {
            hint.hidden = true;

            try {
                window.localStorage.setItem('tnf_ep_zoom_hint_seen', '1');
            } catch {
                // Ignore storage failures.
            }
        }, 4000);
    }

    bindActions() {
        this.root.querySelectorAll('[data-ep-action]').forEach((button) => {
            button.addEventListener('click', () => {
                const action = button.dataset.epAction;

                if (action === 'prev') {
                    this.setPage(this.currentPage - 1);
                } else if (action === 'next') {
                    this.setPage(this.currentPage + 1);
                } else if (action === 'zoom-in') {
                    this.userZoomFactor = Math.min(3, this.userZoomFactor + 0.15);
                    void this.applyPageZoom();
                } else if (action === 'zoom-out') {
                    this.userZoomFactor = Math.max(0.6, this.userZoomFactor - 0.15);
                    void this.applyPageZoom();
                } else if (action === 'zoom-reset') {
                    this.fitPageToView();
                } else if (action === 'clip') {
                    this.toggleClipMode();
                } else if (action === 'share') {
                    this.openShareModal();
                }
            });
        });

        this.root.querySelectorAll('[data-ep-share-modal-close]').forEach((el) => {
            el.addEventListener('click', () => this.closeShareModal());
        });

        this.root.querySelectorAll('[data-ep-modal-close]').forEach((el) => {
            el.addEventListener('click', () => this.closeClipModal());
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                if (! this.els.shareModal?.classList.contains('hidden')) {
                    this.closeShareModal();
                } else if (! this.els.clipModal?.classList.contains('hidden')) {
                    this.closeClipModal();
                } else if (this.clipMode) {
                    this.toggleClipMode(true);
                }

                return;
            }

            const target = event.target;
            const typing = target instanceof HTMLElement && (
                target.tagName === 'INPUT'
                || target.tagName === 'TEXTAREA'
                || target.tagName === 'SELECT'
                || target.isContentEditable
            );

            if (typing || this.clipMode || this.config.clipMode) {
                return;
            }

            if (event.key === 'ArrowLeft' || event.key === 'PageUp') {
                event.preventDefault();
                this.setPage(this.currentPage - 1);
            } else if (event.key === 'ArrowRight' || event.key === 'PageDown') {
                event.preventDefault();
                this.setPage(this.currentPage + 1);
            }
        });

        this.root.querySelector('[data-ep-clip-more-options]')?.addEventListener('click', () => {
            this.els.clipPresets?.classList.add('is-expanded');
        });

        this.els.clipCopyBtn?.addEventListener('click', () => this.copyClipUrl());
        this.els.shareCopyBtn?.addEventListener('click', () => this.copyShareUrl());
        this.els.clipDownload?.addEventListener('click', () => this.downloadClipPreview());

        if (navigator.share && this.els.clipNativeBtn) {
            this.els.clipNativeBtn.classList.remove('hidden');
            this.els.clipNativeBtn.addEventListener('click', () => this.nativeShareClip());
        }

        if (navigator.share && this.els.shareNativeBtn) {
            this.els.shareNativeBtn.classList.remove('hidden');
            this.els.shareNativeBtn.addEventListener('click', () => this.nativeShareEdition());
        }

        this.root.querySelectorAll('[data-ep-clip-float-cancel]').forEach((button) => {
            button.addEventListener('click', () => this.toggleClipMode(true));
        });
        this.root.querySelectorAll('[data-ep-clip-float-share]').forEach((button) => {
            button.addEventListener('click', () => this.confirmClipShare());
        });

        this.els.clipPresets?.addEventListener('click', (event) => {
            const drawBtn = event.target.closest('[data-ep-clip-draw]');

            if (drawBtn) {
                this.enterClipDrawMode();

                return;
            }

            const preset = event.target.closest('[data-ep-clip-preset]');

            if (preset?.dataset.epClipPreset) {
                this.applyClipPreset(preset.dataset.epClipPreset);
            }
        });
    }

    bindTouchZoom() {
        // Mobile uses explicit −/+ buttons so one-finger scroll is never blocked by pinch/double-tap.
        if (this.isCoarsePointer()) {
            return;
        }

        const stage = this.els.stage;

        if (! stage || ! this.isCoarsePointer()) {
            return;
        }

        const touchDistance = (touches) => {
            const dx = touches[0].clientX - touches[1].clientX;
            const dy = touches[0].clientY - touches[1].clientY;

            return Math.hypot(dx, dy);
        };

        stage.addEventListener('touchstart', (event) => {
            if (this.clipMode) {
                return;
            }

            if (event.touches.length === 2) {
                this.pinchStartDistance = touchDistance(event.touches);
                this.pinchStartZoom = this.userZoomFactor;
                return;
            }

            if (event.touches.length === 1) {
                const now = Date.now();

                if (now - this.lastTapAt < 320) {
                    event.preventDefault();

                    if (Math.abs(this.userZoomFactor - 1) < 0.05) {
                        this.userZoomFactor = 2;
                    } else {
                        this.fitPageToView();
                    }

                    void this.applyPageZoom();
                }

                this.lastTapAt = now;
            }
        }, { passive: false });

        stage.addEventListener('touchmove', (event) => {
            if (this.clipMode || event.touches.length !== 2 || ! this.pinchStartDistance) {
                return;
            }

            event.preventDefault();

            const scale = touchDistance(event.touches) / this.pinchStartDistance;
            this.userZoomFactor = Math.min(3, Math.max(0.75, this.pinchStartZoom * scale));
            void this.applyPageZoom();
        }, { passive: false });

        stage.addEventListener('touchend', () => {
            this.pinchStartDistance = 0;
        });
    }

    getEditionShareUrl() {
        if (this.config.shareUrl?.startsWith('http')) {
            return this.config.shareUrl;
        }

        return new URL(this.config.shareUrl || window.location.pathname, window.location.origin).href;
    }

    openShareModal() {
        const shareUrl = this.getEditionShareUrl();

        if (this.els.shareUrl) {
            this.els.shareUrl.value = shareUrl;
        }

        this.updateShareLinks(this.els.editionShare, shareUrl, this.config.title, this.els.shareOpen);
        this.els.shareModal?.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        if (! this.isCoarsePointer()) {
            this.els.shareUrl?.focus();
            this.els.shareUrl?.select();
        }
    }

    closeShareModal() {
        this.els.shareModal?.classList.add('hidden');

        if (this.els.clipModal?.classList.contains('hidden') && ! this.clipMode) {
            document.body.style.overflow = '';
        }
    }

    async copyShareUrl() {
        const button = this.els.shareCopyBtn;
        const url = this.els.shareUrl?.value || this.getEditionShareUrl();

        if (! url || ! button) {
            return;
        }

        const copyIcon = button.querySelector('.tnf-ep-clip-copy-icon');
        const copiedIcon = button.querySelector('.tnf-ep-clip-copied-icon');
        const copyText = button.querySelector('.tnf-ep-clip-copy-text');

        try {
            await navigator.clipboard.writeText(url);
            copyIcon?.classList.add('hidden');
            copiedIcon?.classList.remove('hidden');
            button.classList.add('tnf-ep-clip-copy-btn--copied');
            if (copyText) {
                copyText.textContent = 'Copied';
            }

            window.setTimeout(() => {
                copyIcon?.classList.remove('hidden');
                copiedIcon?.classList.add('hidden');
                button.classList.remove('tnf-ep-clip-copy-btn--copied');
                if (copyText) {
                    copyText.textContent = 'Copy';
                }
            }, 2000);
        } catch {
            // Clipboard unavailable
        }
    }

    async nativeShareEdition() {
        const url = this.getEditionShareUrl();

        if (! url || ! navigator.share) {
            return;
        }

        try {
            await navigator.share({
                title: this.config.title,
                text: this.config.title,
                url,
            });
        } catch {
            // User cancelled or share unavailable
        }
    }

    async copyClipUrl() {
        const button = this.els.clipCopyBtn;
        const url = this.activeClipUrl || this.els.clipUrl?.value;

        if (!url || !button) {
            return;
        }

        const copyIcon = button.querySelector('.tnf-ep-clip-copy-icon');
        const copiedIcon = button.querySelector('.tnf-ep-clip-copied-icon');
        const copyText = button.querySelector('.tnf-ep-clip-copy-text');

        try {
            await navigator.clipboard.writeText(url);
            copyIcon?.classList.add('hidden');
            copiedIcon?.classList.remove('hidden');
            button.classList.add('tnf-ep-clip-copy-btn--copied');
            if (copyText) {
                copyText.textContent = 'Copied!';
            }

            setTimeout(() => {
                copyIcon?.classList.remove('hidden');
                copiedIcon?.classList.add('hidden');
                button.classList.remove('tnf-ep-clip-copy-btn--copied');
                if (copyText) {
                    copyText.textContent = 'Copy';
                }
            }, 2000);
        } catch {
            if (copyText) {
                copyText.textContent = 'Failed';
            }
        }
    }

    async nativeShareClip() {
        const url = this.activeClipUrl || this.els.clipUrl?.value;

        if (!url || !navigator.share) {
            return;
        }

        try {
            await navigator.share({
                title: this.config.title,
                text: 'TNF Today newspaper clip',
                url,
            });
        } catch {
            // User cancelled or share unavailable
        }
    }

    async setPage(page, pushHistory = true) {
        const next = Math.max(1, Math.min(page, this.pageCount));

        if (next !== this.currentPage && this.clipMode) {
            this.toggleClipMode(true);
        }

        this.currentPage = next;

        if (this.pdfDoc) {
            this.setPdfLoading(true);
            await this.renderPdfPage(next);
            this.setPdfLoading(false);
        } else {
            const url = this.getPageUrl(next);
            if (this.els.pageImage && url) {
                this.els.pageImage.removeAttribute('crossorigin');
                this.els.pageImage.classList.remove('hidden');

                await new Promise((resolve) => {
                    const onReady = () => {
                        this.pageWidth = this.els.pageImage.naturalWidth;
                        this.pageHeight = this.els.pageImage.naturalHeight;
                        resolve();
                    };

                    const onError = async () => {
                        if (this.config.pdfUrl && ! this.pdfDoc) {
                            await this.initPdfFallback();

                            if (this.pdfDoc) {
                                await this.renderPdfPage(next);
                            }
                        }

                        resolve();
                    };

                    this.els.pageImage.onload = onReady;
                    this.els.pageImage.onerror = onError;
                    this.els.pageImage.src = url;

                    if (this.els.pageImage.complete && this.els.pageImage.naturalWidth) {
                        onReady();
                    } else if (this.els.pageImage.complete && ! this.els.pageImage.naturalWidth) {
                        void onError();
                    }
                });

                this.fitPageToView();
            }
            if (this.els.pdfCanvas) {
                this.els.pdfCanvas.classList.add('hidden');
            }
        }

        this.updateUi();

        if (this.els.stage) {
            this.els.stage.scrollTop = 0;
            this.els.stage.scrollLeft = 0;
        }

        if (! this.isCoarsePointer() && window.matchMedia('(min-width: 1024px)').matches) {
            const wrap = this.els.stageWrap;
            const top = wrap ? wrap.getBoundingClientRect().top + window.scrollY - 80 : 0;
            window.scrollTo({ top: Math.max(0, top), behavior: 'auto' });
        }

        if (pushHistory) {
            const url = new URL(window.location.href);
            url.searchParams.set('tnf_pg', String(next));
            url.searchParams.delete('tnf_clip');
            ['tnf_cx', 'tnf_cy', 'tnf_cw', 'tnf_ch'].forEach((key) => url.searchParams.delete(key));
            window.history.replaceState({}, '', url);
        }
    }

    updateUi() {
        if (this.els.pageSelect) {
            this.els.pageSelect.value = String(this.currentPage);
        }

        if (this.els.mobilePageSelect) {
            this.els.mobilePageSelect.value = String(this.currentPage);
        }

        if (this.els.mobilePage) {
            this.els.mobilePage.textContent = this.pageLabel();
        }

        this.root.querySelectorAll('.tnf-ep-thumb').forEach((thumb) => {
            thumb.classList.toggle('is-active', Number(thumb.dataset.page) === this.currentPage);
        });

        this.renderPager();
        this.updateZoomLabel();
        this.syncChromeHeights();

        this.root.querySelectorAll('[data-ep-action="prev"]').forEach((button) => {
            button.disabled = this.currentPage <= 1;
        });
        this.root.querySelectorAll('[data-ep-action="next"]').forEach((button) => {
            button.disabled = this.currentPage >= this.pageCount;
        });
    }

    renderPager() {
        if (! this.els.pager) {
            return;
        }

        this.els.pager.innerHTML = '';

        this.pagerPages().forEach((item) => {
            if (item === '…') {
                const dots = document.createElement('span');
                dots.className = 'tnf-ep-pager-ellipsis';
                dots.textContent = '…';
                dots.setAttribute('aria-hidden', 'true');
                this.els.pager.appendChild(dots);
                return;
            }

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'tnf-ep-pager-btn' + (item === this.currentPage ? ' is-active' : '');
            btn.textContent = String(item);
            btn.setAttribute('aria-label', `Page ${item}`);
            btn.setAttribute('aria-current', item === this.currentPage ? 'page' : 'false');
            btn.addEventListener('click', () => this.setPage(item));
            this.els.pager.appendChild(btn);
        });
    }

    bindResizeHandler() {
        if (this.resizeHandler) {
            return;
        }

        this.resizeHandler = () => {
            window.requestAnimationFrame(() => {
                this.syncChromeHeights();
                void this.applyPageZoom();
            });
        };

        window.addEventListener('resize', this.resizeHandler, { passive: true });
    }

    calculateFitZoom() {
        const stage = this.els.stage;
        const width = this.pageWidth;

        if (! stage || ! width) {
            this.fitZoom = 1;
            return;
        }

        const padding = 16;
        const availableWidth = Math.max(200, stage.clientWidth - padding);

        // Match newspaper e-papers: fill the column width, scroll vertically for height.
        this.fitZoom = availableWidth / width;
    }

    fitPageToView() {
        this.userZoomFactor = 1;
        void this.applyPageZoom();
    }

    updateStageLayout(cssWidth, cssHeight) {
        const inner = this.els.stageInner;
        const spacer = this.els.stageSpacer;

        if (! inner || ! spacer) {
            return;
        }

        spacer.style.width = `${cssWidth}px`;
        spacer.style.height = `${cssHeight}px`;
        inner.style.width = `${cssWidth}px`;
        inner.style.height = `${cssHeight}px`;

        if (this.isCoarsePointer()) {
            inner.style.position = 'relative';
            inner.style.left = 'auto';
            inner.style.transform = 'none';
        } else {
            inner.style.position = '';
            inner.style.left = '';
            inner.style.transform = 'translateX(-50%)';
        }
    }

    async applyPageZoom() {
        const width = this.pageWidth;
        const height = this.pageHeight;

        if (! width || ! height) {
            return;
        }

        const stage = this.els.stage;
        const spacer = this.els.stageSpacer;
        let scrollAnchor = null;

        if (stage && spacer) {
            const overflowY = getComputedStyle(stage).overflowY;
            const usesStageScroll = overflowY === 'auto' || overflowY === 'scroll';
            const prevW = Number.parseFloat(spacer.style.width) || stage.scrollWidth || 1;
            const prevH = Number.parseFloat(spacer.style.height) || stage.scrollHeight || 1;

            scrollAnchor = {
                usesStageScroll,
                ratioX: (stage.scrollLeft + stage.clientWidth * 0.5) / prevW,
                ratioY: usesStageScroll
                    ? (stage.scrollTop + stage.clientHeight * 0.5) / prevH
                    : null,
            };
        }

        this.calculateFitZoom();

        const cssWidth = width * this.effectiveZoom;
        const cssHeight = height * this.effectiveZoom;

        this.updateStageLayout(cssWidth, cssHeight);

        if (this.pdfDoc && this.els.pdfCanvas) {
            this.els.pdfCanvas.style.width = `${cssWidth}px`;
            this.els.pdfCanvas.style.height = `${cssHeight}px`;

            const neededQuality = this.effectiveZoom
                * (window.devicePixelRatio || 1)
                * (this.isCoarsePointer() ? 1.2 : 1.75);

            if (! this.cachedPdfRenderScale || neededQuality > this.cachedPdfRenderScale * 1.18) {
                if (this.pdfZoomRenderTimer) {
                    window.clearTimeout(this.pdfZoomRenderTimer);
                }

                this.pdfZoomRenderTimer = window.setTimeout(() => {
                    this.pdfZoomRenderTimer = null;
                    void this.renderPdfPage(this.currentPage).then(() => {
                        this.cachedPdfRenderScale = neededQuality;
                    });
                }, this.isCoarsePointer() ? 220 : 90);
            }
        } else if (this.els.pageImage && ! this.els.pageImage.classList.contains('hidden')) {
            this.els.pageImage.style.width = `${cssWidth}px`;
            this.els.pageImage.style.height = 'auto';
        }

        this.updateZoomLabel();

        if (stage && scrollAnchor) {
            if (scrollAnchor.usesStageScroll) {
                stage.scrollLeft = Math.max(0, scrollAnchor.ratioX * cssWidth - stage.clientWidth * 0.5);
                stage.scrollTop = Math.max(0, scrollAnchor.ratioY * cssHeight - stage.clientHeight * 0.5);
            }
        }

        if (this.clipMode) {
            requestAnimationFrame(() => this.syncClipOverlay());
        }
    }

    updateThumbForPage(page) {
        const src = this.getPageUrl(page) || this.pdfThumbCache[page];

        if (! src) {
            return;
        }

        this.root.querySelectorAll(`.tnf-ep-thumb[data-page="${page}"] img`).forEach((img) => {
            img.src = src;
            img.classList.remove('tnf-ep-thumb-placeholder');
        });
    }

    async cachePdfThumbnail(page, pdfPage, baseViewport) {
        if (this.pdfThumbCache[page]) {
            this.updateThumbForPage(page);
            return;
        }

        const thumbWidth = 112;
        const scale = thumbWidth / baseViewport.width;
        const thumbViewport = pdfPage.getViewport({ scale });
        const canvas = document.createElement('canvas');
        canvas.width = thumbViewport.width;
        canvas.height = thumbViewport.height;

        const context = canvas.getContext('2d', { alpha: false });

        if (! context) {
            return;
        }

        try {
            await pdfPage.render({ canvasContext: context, viewport: thumbViewport }).promise;
            this.pdfThumbCache[page] = canvas.toDataURL('image/jpeg', 0.82);
            this.updateThumbForPage(page);
        } catch {
            // Thumbnail generation is best-effort.
        }
    }

    async prefetchPdfThumbnails() {
        if (! this.pdfDoc) {
            return;
        }

        for (let page = 1; page <= this.pageCount; page++) {
            if (this.pdfThumbCache[page]) {
                this.updateThumbForPage(page);
                continue;
            }

            try {
                const pdfPage = await this.pdfDoc.getPage(page);
                const baseViewport = pdfPage.getViewport({ scale: 1 });
                await this.cachePdfThumbnail(page, pdfPage, baseViewport);
            } catch {
                // Skip failed thumbnails.
            }
        }
    }

    updateZoomLabel() {
        const resetBtn = this.root.querySelector('[data-ep-action="zoom-reset"]');
        if (resetBtn) {
            resetBtn.textContent = this.userZoomFactor === 1
                ? 'Fit'
                : `${Math.round(this.effectiveZoom * 100)}%`;
        }
    }

    toggleClipMode(forceOff = false) {
        this.clipMode = forceOff ? false : ! this.clipMode;

        this.root.classList.toggle('is-clip-mode', this.clipMode);
        this.els.clipHint?.classList.add('hidden');
        this.root.querySelectorAll('[data-ep-action="clip"]').forEach((button) => {
            button.classList.toggle('is-active', this.clipMode);
            button.setAttribute('aria-pressed', this.clipMode ? 'true' : 'false');
        });

        if (this.clipMode) {
            this.resolveClipElements();

            if (! this.els.clipScreen) {
                console.error('TNF ePaper: clip UI is missing. Run npm run build on the server and hard refresh.');
                this.clipMode = false;
                this.root.classList.remove('is-clip-mode');
                this.root.querySelectorAll('[data-ep-action="clip"]').forEach((button) => {
                    button.classList.remove('is-active');
                    button.setAttribute('aria-pressed', 'false');
                });
                return;
            }

            document.body.classList.add('tnf-ep-is-clipping');
            this.els.clipBar?.classList.remove('hidden');
            void this.prepareClipMode();
        } else {
            this.unbindClipDrag();
            this.unmountClipScreen();
        }
    }

    resolveClipElements() {
        const selectors = {
            clipBar: '[data-ep-clip-bar]',
            clipScreen: '[data-ep-clip-screen]',
            clipWorkspaceHint: '[data-ep-clip-workspace-hint]',
            clipPresets: '[data-ep-clip-presets]',
            clipFloatShare: '[data-ep-clip-float-share]',
            clipFloatCancel: '[data-ep-clip-float-cancel]',
        };

        Object.entries(selectors).forEach(([key, selector]) => {
            this.els[key] = this.root.querySelector(selector) || document.querySelector(selector);
        });
    }

    showClipWorkspaceError(message) {
        if (this.els.clipWorkspaceHint) {
            this.els.clipWorkspaceHint.textContent = message;
        }
    }

    async prepareClipMode() {
        try {
            this.setPdfLoading(true);
            const imageReady = await this.prepareClipCapture();

            if (! imageReady) {
                throw new Error('Could not capture the current page for clipping.');
            }

            this.clipWorkspaceActive = false;
            this.mountClipScreen();
            this.bindClipDrag();
            this.applyClipPreset('lead');
            this.updateClipShareButtonsState();
            this.setClipInstruction('Adjust selection, then Share');
        } catch (error) {
            console.error('TNF ePaper: clip mode failed.', error);
            this.showClipWorkspaceError('Could not prepare the clip. Cancel and try again.');
            this.toggleClipMode(true);
        } finally {
            this.setPdfLoading(false);
        }
    }

    async waitForImageElement(img) {
        if (img.complete && img.naturalWidth > 0) {
            return;
        }

        await new Promise((resolve, reject) => {
            img.addEventListener('load', resolve, { once: true });
            img.addEventListener('error', () => reject(new Error('Image failed to load')), { once: true });
        });
    }

    imageElementToDataUrl(imageLike) {
        const naturalWidth = imageLike.naturalWidth || imageLike.width;
        const naturalHeight = imageLike.naturalHeight || imageLike.height;

        if (! naturalWidth || ! naturalHeight) {
            return null;
        }

        const canvas = document.createElement('canvas');
        canvas.width = naturalWidth;
        canvas.height = naturalHeight;

        const context = canvas.getContext('2d', { alpha: false });

        if (! context) {
            return null;
        }

        context.drawImage(imageLike, 0, 0, naturalWidth, naturalHeight);

        try {
            return canvas.toDataURL('image/jpeg', 0.9);
        } catch (error) {
            console.warn('TNF ePaper: canvas export failed.', error);

            return null;
        }
    }

    async fetchImageAsDataUrl(url) {
        try {
            const response = await fetch(url, { credentials: 'same-origin' });

            if (! response.ok) {
                return null;
            }

            const blob = await response.blob();
            const objectUrl = URL.createObjectURL(blob);
            const img = new Image();

            try {
                await new Promise((resolve, reject) => {
                    img.onload = resolve;
                    img.onerror = reject;
                    img.src = objectUrl;
                });

                return this.imageElementToDataUrl(img);
            } finally {
                URL.revokeObjectURL(objectUrl);
            }
        } catch (error) {
            console.warn('TNF ePaper: fetch image for clip failed.', error);

            return null;
        }
    }

    async ensurePdfDocForClip() {
        if (this.pdfDoc || ! this.config.pdfUrl) {
            return Boolean(this.pdfDoc);
        }

        const pdfJsReady = await this.waitForPdfJs();

        if (! pdfJsReady) {
            console.error('TNF ePaper: PDF.js is not loaded — clip needs it when page images are unavailable.');

            return false;
        }

        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        try {
            const loadingTask = pdfjsLib.getDocument({
                url: this.config.pdfUrl,
                rangeChunkSize: 65536,
                disableStream: false,
                disableAutoFetch: false,
            });
            this.pdfDoc = await loadingTask.promise;

            return true;
        } catch (error) {
            console.error('TNF ePaper: failed to open PDF for clip.', error);

            return false;
        }
    }

    async prepareClipCapture() {
        const dataUrl = await this.captureCurrentPagePreview();

        if (! dataUrl) {
            return false;
        }

        this.clipWorkspaceImageDataUrl = dataUrl;

        return true;
    }

    getClipMasterScale(pageWidth) {
        const targetWidth = this.isCoarsePointer() ? 1200 : 1600;

        return Math.min(2.5, Math.max(1.25, targetWidth / pageWidth));
    }

    getClipExportScale(pageWidth, clipWidthFraction) {
        const cropPageWidth = Math.max(1, pageWidth * clipWidthFraction);
        const targetCropPx = this.isCoarsePointer() ? 1200 : 1800;

        return Math.min(4, Math.max(2, targetCropPx / cropPageWidth));
    }

    async captureCurrentPagePreview() {
        if (this.config.pdfUrl) {
            if (await this.ensurePdfDocForClip()) {
                this.clipCaptureSource = 'pdf';

                return this.renderPdfClipMasterDataUrl(this.currentPage);
            }
        }

        const pageUrl = this.getPageUrl(this.currentPage);

        if (pageUrl) {
            this.clipCaptureSource = 'page-image';
            const dataUrl = await this.fetchImageAsDataUrl(pageUrl);

            if (dataUrl) {
                return dataUrl;
            }
        }

        const pageImage = this.els.pageImage;

        if (pageImage && ! pageImage.classList.contains('hidden') && pageImage.naturalWidth) {
            this.clipCaptureSource = 'page-image';

            return this.imageElementToDataUrl(pageImage);
        }

        if (this.els.pdfCanvas && ! this.els.pdfCanvas.classList.contains('hidden') && this.els.pdfCanvas.width > 0) {
            this.clipCaptureSource = 'pdf';

            return this.imageElementToDataUrl(this.els.pdfCanvas);
        }

        return null;
    }

    async renderPdfClipMasterDataUrl(pageNum) {
        if (! this.pdfDoc) {
            return null;
        }

        const pdfPage = await this.pdfDoc.getPage(pageNum);
        const baseViewport = pdfPage.getViewport({ scale: 1 });
        const scale = this.getClipMasterScale(baseViewport.width);
        const viewport = pdfPage.getViewport({ scale });
        const canvas = document.createElement('canvas');
        canvas.width = Math.floor(viewport.width);
        canvas.height = Math.floor(viewport.height);

        const context = canvas.getContext('2d', { alpha: false });

        if (! context) {
            return null;
        }

        await pdfPage.render({ canvasContext: context, viewport }).promise;
        this.clipMasterScale = scale;

        return canvas.toDataURL('image/jpeg', 0.92);
    }

    getStageWrapRect() {
        return this.els.stageWrap?.getBoundingClientRect() || {
            left: 0,
            top: 0,
            width: window.innerWidth,
            height: window.innerHeight,
            right: window.innerWidth,
            bottom: window.innerHeight,
        };
    }

    getImageScreenRect() {
        const source = this.getClipSourceElement();

        return source ? source.getBoundingClientRect() : null;
    }

    getImageOverlayRect() {
        const imageRect = this.getImageScreenRect();
        const referenceEl = this.els.clipScreen || this.els.stageInner;
        const reference = referenceEl?.getBoundingClientRect();

        if (! imageRect || ! reference || imageRect.width < 1 || imageRect.height < 1) {
            return null;
        }

        return {
            left: imageRect.left - reference.left,
            top: imageRect.top - reference.top,
            width: imageRect.width,
            height: imageRect.height,
            right: imageRect.right - reference.left,
            bottom: imageRect.bottom - reference.top,
        };
    }

    getClipOverlayBounds() {
        const screen = this.els.clipScreen || this.els.stageInner;

        if (! screen) {
            return {
                width: window.innerWidth,
                height: window.innerHeight,
                offsetLeft: 0,
                offsetTop: 0,
            };
        }

        return {
            width: screen.clientWidth,
            height: screen.clientHeight,
            offsetLeft: 0,
            offsetTop: 0,
        };
    }

    scheduleClipOverlaySync(markComplete = false) {
        const sync = () => this.syncClipOverlay(markComplete);

        sync();
        requestAnimationFrame(() => {
            sync();
            requestAnimationFrame(sync);
        });
    }

    isPointInsideImage(clientX, clientY) {
        const rect = this.getImageScreenRect();

        if (! rect) {
            return false;
        }

        return clientX >= rect.left
            && clientX <= rect.right
            && clientY >= rect.top
            && clientY <= rect.bottom;
    }

    clampPointToImage(clientX, clientY) {
        const rect = this.getImageScreenRect();

        if (! rect) {
            return { x: clientX, y: clientY };
        }

        return {
            x: Math.max(rect.left, Math.min(rect.right, clientX)),
            y: Math.max(rect.top, Math.min(rect.bottom, clientY)),
        };
    }

    clampOverlayPointToImage(left, top) {
        const rect = this.getImageOverlayRect();

        if (! rect) {
            return { left, top };
        }

        return {
            left: Math.max(rect.left, Math.min(rect.right, left)),
            top: Math.max(rect.top, Math.min(rect.bottom, top)),
        };
    }

    normalizedToOverlayRect(normalized) {
        const imageRect = this.getImageOverlayRect();

        if (! imageRect || ! normalized) {
            return null;
        }

        return {
            left: imageRect.left + (normalized.x * imageRect.width),
            top: imageRect.top + (normalized.y * imageRect.height),
            width: normalized.w * imageRect.width,
            height: normalized.h * imageRect.height,
        };
    }

    overlayRectToNormalized(left, top, width, height) {
        const imageRect = this.getImageOverlayRect();

        if (! imageRect || width < 1 || height < 1) {
            return null;
        }

        const clipLeft = Math.max(left, imageRect.left);
        const clipTop = Math.max(top, imageRect.top);
        const clipRight = Math.min(left + width, imageRect.right);
        const clipBottom = Math.min(top + height, imageRect.bottom);
        const clipWidth = clipRight - clipLeft;
        const clipHeight = clipBottom - clipTop;
        const minSize = this.isCoarsePointer() ? 12 : 20;

        if (clipWidth < minSize || clipHeight < minSize) {
            return null;
        }

        const normalized = {
            x: (clipLeft - imageRect.left) / imageRect.width,
            y: (clipTop - imageRect.top) / imageRect.height,
            w: clipWidth / imageRect.width,
            h: clipHeight / imageRect.height,
        };

        return this.clampNormalizedRect(normalized);
    }

    clampNormalizedRect(normalized) {
        if (! normalized) {
            return null;
        }

        let { x, y, w, h } = normalized;
        x = Math.max(0, Math.min(1, x));
        y = Math.max(0, Math.min(1, y));
        w = Math.max(0.001, Math.min(1 - x, w));
        h = Math.max(0.001, Math.min(1 - y, h));

        return { x, y, w, h };
    }

    normalizedToClip(normalized) {
        const clamped = this.clampNormalizedRect(normalized);

        if (! clamped) {
            return null;
        }

        return {
            page: this.currentPage,
            x: clamped.x,
            y: clamped.y,
            w: clamped.w,
            h: clamped.h,
        };
    }

    getClipPreset(name) {
        const presets = {
            lead: { x: 0.06, y: 0.05, w: 0.88, h: 0.4 },
            top: { x: 0.04, y: 0.04, w: 0.92, h: 0.5 },
            full: { x: 0.02, y: 0.02, w: 0.96, h: 0.96 },
        };

        if (name === 'reset') {
            return presets.lead;
        }

        return presets[name] || presets.lead;
    }

    getDefaultClipNormalized() {
        return this.getClipPreset('lead');
    }

    applyClipPreset(name) {
        this.dismissFirstClipHint();
        this.clipDrawMode = false;
        this.updateClipCatcherInteraction();
        this.clipNormalized = this.getClipPreset(name === 'reset' ? 'lead' : name);
        this.syncClipOverlay(true);
        this.setClipInstruction(this.clipReadyMessage());
        this.updateClipPresetButtons(name === 'reset' ? 'lead' : name);
    }

    updateClipPresetButtons(activeName = 'lead') {
        this.els.clipPresets?.querySelectorAll('[data-ep-clip-preset]').forEach((button) => {
            const key = button.dataset.epClipPreset;

            button.classList.toggle('is-active', activeName !== null && key === activeName);
        });

        const drawBtn = this.els.clipPresets?.querySelector('[data-ep-clip-draw]');

        if (drawBtn) {
            drawBtn.classList.toggle('is-active', this.clipDrawMode);
            drawBtn.setAttribute('aria-pressed', this.clipDrawMode ? 'true' : 'false');
        }
    }

    updateClipCatcherInteraction() {
        const catcher = this.els.clipScreen?.querySelector('[data-ep-clip-catcher]');

        if (! catcher || ! this.els.clipScreen) {
            return;
        }

        // Scroll-first by default: empty page area scrolls so users can reach lower stories.
        // Draw mode (optional) turns the catcher back on for a freehand selection.
        if (this.clipDrawMode) {
            catcher.classList.add('is-draw-active');
            this.els.clipScreen.classList.add('is-draw-mode');
            this.els.clipScreen.classList.remove('is-scroll-mode');
        } else {
            catcher.classList.remove('is-draw-active');
            this.els.clipScreen.classList.add('is-scroll-mode');
            this.els.clipScreen.classList.remove('is-draw-mode');
        }
    }

    enterClipDrawMode() {
        this.dismissFirstClipHint();
        this.clipDrawMode = true;
        this.updateClipCatcherInteraction();
        this.updateClipPresetButtons(null);
        this.setClipInstruction('Drag on the page to draw a new selection');
    }

    exitClipDrawMode() {
        this.clipDrawMode = false;
        this.updateClipCatcherInteraction();
        this.updateClipPresetButtons(null);
        this.setClipInstruction(this.clipReadyMessage());
    }

    clipHintMessage() {
        return 'Drag edges to resize, or drag inside to move';
    }

    clipReadyMessage() {
        return 'Scroll to find a story, adjust the box, then Share';
    }

    setClipInstruction(message, { showOverlay = false, autoHideMs = null } = {}) {
        if (this.els.clipWorkspaceHint) {
            this.els.clipWorkspaceHint.textContent = message;
        }

        const overlayHint = this.els.clipScreen?.querySelector('[data-ep-clip-hint-text]');

        if (overlayHint) {
            if (this.isCoarsePointer() || ! showOverlay) {
                overlayHint.classList.add('hidden');
            } else {
                overlayHint.textContent = message;
                overlayHint.classList.remove('hidden');
            }
        }

        if (this.clipInstructionTimer) {
            window.clearTimeout(this.clipInstructionTimer);
            this.clipInstructionTimer = null;
        }

        const hideAfter = autoHideMs ?? (this.isCoarsePointer() && message !== this.clipReadyMessage() ? 4500 : 0);

        if (hideAfter > 0) {
            this.clipInstructionTimer = window.setTimeout(() => {
                this.clipInstructionTimer = null;
                this.setClipInstruction(this.clipReadyMessage(), { autoHideMs: 0 });
            }, hideAfter);
        }
    }

    adjustClipPageZoom(delta) {
        this.clipPageZoom = Math.min(3.5, Math.max(0.5, this.clipPageZoom + delta));
        this.applyClipPageZoom();
    }

    applyClipPageZoom() {
        const img = this.els.clipWorkspaceImage;
        const page = this.els.clipWorkspacePage;

        if (! img || ! page || ! this.clipPageBaseWidth) {
            return;
        }

        const width = Math.round(this.clipPageBaseWidth * this.clipPageZoom);
        img.style.width = `${width}px`;
        img.style.maxWidth = 'none';
        page.style.width = `${width}px`;

        if (this.els.clipZoomLabel) {
            this.els.clipZoomLabel.textContent = `${Math.round(this.clipPageZoom * 100)}%`;
        }

        this.scheduleClipOverlaySync(true);
    }

    resetClipPageZoom() {
        this.clipPageZoom = 1;
        this.clipPageBaseWidth = 0;

        const img = this.els.clipWorkspaceImage;
        const page = this.els.clipWorkspacePage;

        if (img) {
            img.style.width = '';
            img.style.maxWidth = '';
        }

        if (page) {
            page.style.width = '';
        }

        if (this.els.clipZoomLabel) {
            this.els.clipZoomLabel.textContent = '100%';
        }
    }

    showFirstClipHint() {
        if (localStorage.getItem('tnf_ep_clip_hint_seen') || this.isCoarsePointer()) {
            return;
        }

        this.setClipInstruction(
            'Drag on the page to select a headline or article',
            { showOverlay: true },
        );
        this.getClipScreenElements()?.box?.classList.add('tnf-ep-clip-box--pulse');

        window.setTimeout(() => this.dismissFirstClipHint(), 6000);
    }

    dismissFirstClipHint() {
        localStorage.setItem('tnf_ep_clip_hint_seen', '1');
        this.getClipScreenElements()?.box?.classList.remove('tnf-ep-clip-box--pulse');
        this.setClipInstruction(this.clipReadyMessage());
    }

    scheduleLiveClipPreview() {
        if (this.livePreviewFrame) {
            cancelAnimationFrame(this.livePreviewFrame);
        }

        this.livePreviewFrame = requestAnimationFrame(() => {
            this.livePreviewFrame = null;
            this.updateLiveClipPreview();
        });
    }

    updateLiveClipPreview() {
        const preview = this.els.clipLivePreview;
        const wrap = this.els.clipLivePreviewWrap;
        const source = this.els.clipWorkspaceImage;

        if (! preview || ! wrap || ! source || ! this.clipNormalized || ! source.naturalWidth) {
            wrap?.classList.add('hidden');

            return;
        }

        const normalized = this.clampNormalizedRect(this.clipNormalized);

        if (! normalized) {
            wrap?.classList.add('hidden');

            return;
        }
        const sx = Math.round(source.naturalWidth * normalized.x);
        const sy = Math.round(source.naturalHeight * normalized.y);
        const sw = Math.max(1, Math.round(source.naturalWidth * normalized.w));
        const sh = Math.max(1, Math.round(source.naturalHeight * normalized.h));
        const maxWidth = 128;
        const scale = Math.min(1, maxWidth / sw);
        const canvas = document.createElement('canvas');
        canvas.width = Math.max(1, Math.round(sw * scale));
        canvas.height = Math.max(1, Math.round(sh * scale));
        const context = canvas.getContext('2d', { alpha: false });

        if (! context) {
            return;
        }

        context.drawImage(source, sx, sy, sw, sh, 0, 0, canvas.width, canvas.height);

        try {
            preview.src = canvas.toDataURL('image/jpeg', 0.85);
            wrap.classList.remove('hidden');
        } catch {
            wrap.classList.add('hidden');
        }
    }

    setClipShareBusy(busy) {
        this.clipShareBusy = busy;

        this.root.querySelectorAll('[data-ep-clip-float-share]').forEach((button) => {
            button.disabled = busy || ! this.pendingClip;
            button.classList.toggle('is-busy', busy);
        });
    }

    updateClipShareButtonsState() {
        const ready = Boolean(this.pendingClip) && ! this.clipShareBusy;

        this.root.querySelectorAll('[data-ep-clip-float-share]').forEach((button) => {
            button.disabled = ! ready;
            button.classList.toggle('is-ready', ready);
        });
    }

    showClipMobileDock(show) {
        const dock = this.root.querySelector('[data-ep-clip-mobile-dock]');

        if (! dock) {
            return;
        }

        dock.classList.toggle('hidden', ! show);
        dock.setAttribute('aria-hidden', show ? 'false' : 'true');
    }

    mountClipScreen() {
        if (! this.els.clipScreen) {
            return;
        }

        this.els.clipBar?.classList.remove('hidden');
        this.els.clipScreen.classList.remove('hidden');
        this.els.clipScreen.setAttribute('aria-hidden', 'false');
        document.body.classList.add('tnf-ep-is-clipping');
        this.showClipMobileDock(this.isCoarsePointer() || window.matchMedia('(max-width: 1023px)').matches);

        this.pendingClip = null;
        this.clipNormalized = this.getDefaultClipNormalized();
        this.clipWorkspaceActive = false;

        this.setClipInstruction(this.clipReadyMessage());
        this.updateClipPresetButtons('lead');
        this.updateClipCatcherInteraction();

        const ui = this.getClipScreenElements();
        ui?.box?.classList.remove('is-complete');

        this.scheduleClipOverlaySync(true);
        this.showFirstClipHint();
        this.syncChromeHeights();
        requestAnimationFrame(() => {
            this.syncChromeHeights();
            this.syncClipOverlay(true);
        });

        const source = this.getClipSourceElement();
        if (source && ! (source.complete && (source.naturalWidth || source.width))) {
            source.addEventListener('load', () => this.scheduleClipOverlaySync(true), { once: true });
        }

        if (! this.clipScrollHandler) {
            this.clipScrollHandler = () => this.syncClipOverlay();
            this.els.stage?.addEventListener('scroll', this.clipScrollHandler, { passive: true });
            window.addEventListener('resize', this.clipScrollHandler, { passive: true });
        }
    }

    unmountClipScreen() {
        this.els.clipBar?.classList.add('hidden');
        this.els.clipScreen?.classList.add('hidden');
        this.els.clipScreen?.setAttribute('aria-hidden', 'true');
        this.showClipMobileDock(false);
        this.clipWorkspaceActive = false;
        this.clipDrawMode = false;
        this.clipCaptureSource = null;
        document.body.classList.remove('tnf-ep-is-clipping');
        document.body.style.overflow = '';
        this.activePointerId = null;
        this.resetClipScreenSelection();
        this.updateClipShareButtonsState();
        this.syncChromeHeights();

        if (this.clipScrollHandler) {
            this.els.stage?.removeEventListener('scroll', this.clipScrollHandler);
            window.removeEventListener('resize', this.clipScrollHandler);
            this.clipScrollHandler = null;
        }

        if (this.clipPanCleanup) {
            this.clipPanCleanup();
            this.clipPanCleanup = null;
        }
    }

    async shareClipViaWhatsApp() {
        if (! this.pendingClip || this.clipShareBusy) {
            return;
        }

        const clip = { ...this.pendingClip };

        if (navigator.vibrate) {
            navigator.vibrate(12);
        }

        this.setClipShareBusy(true);

        try {
            const clipUrl = await this.fetchSignedClipUrl(clip);
            const encodedTitle = encodeURIComponent(this.config.title);
            const encodedUrl = encodeURIComponent(clipUrl);
            window.open(`https://wa.me/?text=${encodedTitle}%20${encodedUrl}`, '_blank', 'noopener');
            this.dismissFirstClipHint();
            this.toggleClipMode(true);
        } catch (error) {
            console.error('TNF ePaper: WhatsApp share failed.', error);
        } finally {
            this.setClipShareBusy(false);
        }
    }

    syncClipOverlay(markComplete = false) {
        if (! this.clipNormalized) {
            return;
        }

        const overlay = this.normalizedToOverlayRect(this.clipNormalized);

        if (! overlay) {
            return;
        }

        this.updateClipScreenShades(overlay.left, overlay.top, overlay.width, overlay.height);

        const ui = this.getClipScreenElements();
        const clip = this.normalizedToClip(this.clipNormalized);

        if (clip) {
            this.pendingClip = clip;
            ui?.box?.classList.add('is-complete');
        } else if (markComplete) {
            this.clipNormalized = this.getDefaultClipNormalized();
            this.syncClipOverlay(true);
        }

        this.updateClipShareButtonsState();
    }

    getClipScreenElements() {
        const visual = this.els.clipScreen?.querySelector('[data-ep-clip-visual]');

        if (! visual) {
            return null;
        }

        return {
            visual,
            shades: {
                top: visual.querySelector('.tnf-ep-clip-shade--top'),
                left: visual.querySelector('.tnf-ep-clip-shade--left'),
                right: visual.querySelector('.tnf-ep-clip-shade--right'),
                bottom: visual.querySelector('.tnf-ep-clip-shade--bottom'),
            },
            box: visual.querySelector('.tnf-ep-clip-box'),
        };
    }

    updateClipScreenShades(left, top, width, height) {
        const ui = this.getClipScreenElements();

        if (! ui) {
            return;
        }

        const bounds = this.getClipOverlayBounds();
        const originLeft = bounds.offsetLeft;
        const originTop = bounds.offsetTop;
        const stageWidth = bounds.width;
        const stageHeight = bounds.height;
        const boxLeft = originLeft + left;
        const boxTop = originTop + top;

        if (width <= 0 || height <= 0) {
            Object.values(ui.shades).forEach((shade) => {
                if (shade) {
                    shade.style.display = 'none';
                }
            });

            if (ui.box) {
                ui.box.style.display = 'none';
            }

            ui.visual?.classList.remove('is-active');

            return;
        }

        Object.values(ui.shades).forEach((shade) => {
            if (shade) {
                shade.style.display = 'block';
            }
        });

        if (ui.box) {
            ui.box.style.display = 'block';
        }

        Object.assign(ui.shades.top.style, {
            top: `${originTop}px`,
            left: `${originLeft}px`,
            width: `${stageWidth}px`,
            height: `${Math.max(0, top)}px`,
        });
        Object.assign(ui.shades.bottom.style, {
            top: `${boxTop + height}px`,
            left: `${originLeft}px`,
            width: `${stageWidth}px`,
            height: `${Math.max(0, originTop + stageHeight - boxTop - height)}px`,
        });
        Object.assign(ui.shades.left.style, {
            top: `${boxTop}px`,
            left: `${originLeft}px`,
            width: `${Math.max(0, left)}px`,
            height: `${height}px`,
        });
        Object.assign(ui.shades.right.style, {
            top: `${boxTop}px`,
            left: `${boxLeft + width}px`,
            width: `${Math.max(0, originLeft + stageWidth - boxLeft - width)}px`,
            height: `${height}px`,
        });

        Object.assign(ui.box.style, {
            left: `${boxLeft}px`,
            top: `${boxTop}px`,
            width: `${width}px`,
            height: `${height}px`,
        });

        ui.visual.classList.toggle('is-active', width > 0 || height > 0);
    }

    resetClipScreenSelection() {
        const ui = this.getClipScreenElements();

        if (! ui) {
            return;
        }

        this.pendingClip = null;
        this.clipNormalized = null;

        this.updateClipScreenShades(0, 0, 0, 0);
        ui.box?.classList.remove('is-complete');
        ui.visual?.classList.remove('is-active');

        this.updateClipShareButtonsState();
    }

    confirmClipShare() {
        if (! this.pendingClip) {
            return;
        }

        const clip = this.pendingClip;
        this.pendingClip = null;

        if (navigator.vibrate) {
            navigator.vibrate(12);
        }

        this.toggleClipMode(true);
        void this.openClipModal(clip);
    }

    screenSelectionToClip(left, top, width, height) {
        const imageRect = this.getImageScreenRect();

        if (! imageRect || width < 1 || height < 1) {
            return null;
        }

        const clipLeft = Math.max(left, imageRect.left);
        const clipTop = Math.max(top, imageRect.top);
        const clipRight = Math.min(left + width, imageRect.right);
        const clipBottom = Math.min(top + height, imageRect.bottom);
        const clipWidth = clipRight - clipLeft;
        const clipHeight = clipBottom - clipTop;

        const minSize = this.isCoarsePointer() ? 18 : 24;

        if (clipWidth < minSize || clipHeight < minSize) {
            return null;
        }

        return {
            page: this.currentPage,
            x: (clipLeft - imageRect.left) / imageRect.width,
            y: (clipTop - imageRect.top) / imageRect.height,
            w: clipWidth / imageRect.width,
            h: clipHeight / imageRect.height,
        };
    }

    unbindClipDrag() {
        if (this.clipDragCleanup) {
            this.clipDragCleanup();
            this.clipDragCleanup = null;
        }
    }

    clientPointToOverlay(clientX, clientY) {
        const reference = this.els.clipScreen?.getBoundingClientRect()
            || this.els.stageInner?.getBoundingClientRect()
            || this.getStageWrapRect();

        return {
            x: clientX - reference.left,
            y: clientY - reference.top,
        };
    }

    /**
     * While dragging a clip box near the viewport edge, scroll the stage
     * so lower/upper parts of the page stay reachable.
     */
    autoScrollStageDuringClip(clientY) {
        const stage = this.els.stage;

        if (! stage) {
            return;
        }

        const overflowY = getComputedStyle(stage).overflowY;
        const usesStageScroll = overflowY === 'auto' || overflowY === 'scroll';

        if (! usesStageScroll) {
            return;
        }

        const rect = stage.getBoundingClientRect();
        const edge = 56;
        let delta = 0;

        if (clientY < rect.top + edge) {
            delta = -Math.ceil((edge - (clientY - rect.top)) * 0.35);
        } else if (clientY > rect.bottom - edge) {
            delta = Math.ceil((edge - (rect.bottom - clientY)) * 0.35);
        }

        if (delta !== 0) {
            stage.scrollTop += delta;
            this.syncClipOverlay();
        }
    }

    bindClipDrag() {
        const catcherEl = this.els.clipScreen?.querySelector('[data-ep-clip-catcher]');
        const ui = this.getClipScreenElements();
        let interactionMode = null;
        let dragStart = null;
        let startRect = null;
        let lastDragRect = null;
        let captureEl = null;
        let trackingDocument = false;

        const applyOverlayRect = (left, top, width, height) => {
            const normalized = this.overlayRectToNormalized(left, top, width, height);

            if (! normalized) {
                this.setClipInstruction('Selection too small — try again');

                return false;
            }

            this.clipNormalized = normalized;
            this.syncClipOverlay(true);
            this.setClipInstruction(this.clipReadyMessage());

            return true;
        };

        const boxStyleToOverlayRect = () => {
            const elements = this.getClipScreenElements();
            const boxStyle = elements?.box?.style;
            const bounds = this.getClipOverlayBounds();
            const left = Number.parseFloat(boxStyle?.left || '0') - bounds.offsetLeft;
            const top = Number.parseFloat(boxStyle?.top || '0') - bounds.offsetTop;
            const width = Number.parseFloat(boxStyle?.width || '0');
            const height = Number.parseFloat(boxStyle?.height || '0');

            return { left, top, width, height };
        };

        const startDocumentTracking = () => {
            if (trackingDocument) {
                return;
            }

            trackingDocument = true;
            document.addEventListener('pointermove', onPointerMove, { passive: false });
            document.addEventListener('pointerup', onPointerUp);
            document.addEventListener('pointercancel', onPointerUp);
        };

        const stopDocumentTracking = () => {
            if (! trackingDocument) {
                return;
            }

            trackingDocument = false;
            document.removeEventListener('pointermove', onPointerMove);
            document.removeEventListener('pointerup', onPointerUp);
            document.removeEventListener('pointercancel', onPointerUp);
        };

        const finishInteraction = () => {
            const pointerId = this.lastClipPointerId;

            interactionMode = null;
            dragStart = null;
            startRect = null;
            lastDragRect = null;
            this.activePointerId = null;
            this.lastClipPointerId = null;

            if (captureEl && pointerId !== null) {
                try {
                    captureEl.releasePointerCapture?.(pointerId);
                } catch {
                    // Ignore release errors.
                }

                captureEl = null;
            }

            stopDocumentTracking();
        };

        const onPointerDown = (event) => {
            if (this.activePointerId !== null) {
                return;
            }

            if (event.target.closest('[data-ep-clip-float-actions]')) {
                return;
            }

            const handle = event.target.closest('[data-ep-clip-handle]');
            const box = event.target.closest('.tnf-ep-clip-box');
            const onCatcher = event.currentTarget === catcherEl;

            if (! handle && ! box && ! (onCatcher && this.isPointInsideImage(event.clientX, event.clientY))) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            this.dismissFirstClipHint();
            this.setClipInstruction(this.clipHintMessage());
            this.activePointerId = event.pointerId;
            this.lastClipPointerId = event.pointerId;
            captureEl = event.currentTarget;

            try {
                captureEl.setPointerCapture?.(event.pointerId);
            } catch {
                // Pointer capture is optional on some browsers.
            }

            startDocumentTracking();

            const point = this.clientPointToOverlay(event.clientX, event.clientY);
            const overlay = this.normalizedToOverlayRect(this.clipNormalized) || {
                left: point.x,
                top: point.y,
                width: 0,
                height: 0,
            };

            if (handle) {
                interactionMode = `resize-${handle.dataset.epClipHandle}`;
                startRect = { ...overlay };
                dragStart = point;

                return;
            }

            if (box) {
                interactionMode = 'move';
                startRect = { ...overlay };
                dragStart = point;

                return;
            }

            interactionMode = 'draw';
            dragStart = this.clampOverlayPointToImage(point.x, point.y);
            startRect = { left: dragStart.left, top: dragStart.top, width: 0, height: 0 };
            this.updateClipScreenShades(startRect.left, startRect.top, 0, 0);
        };

        const onPointerMove = (event) => {
            if (! interactionMode || event.pointerId !== this.activePointerId) {
                return;
            }

            event.preventDefault();

            const point = this.clientPointToOverlay(event.clientX, event.clientY);
            const clamped = this.clampOverlayPointToImage(point.x, point.y);
            let left = startRect.left;
            let top = startRect.top;
            let width = startRect.width;
            let height = startRect.height;

            if (interactionMode === 'draw') {
                left = Math.min(dragStart.left, clamped.left);
                top = Math.min(dragStart.top, clamped.top);
                width = Math.abs(clamped.left - dragStart.left);
                height = Math.abs(clamped.top - dragStart.top);
            } else if (interactionMode === 'move') {
                const dx = point.x - dragStart.x;
                const dy = point.y - dragStart.y;
                left = startRect.left + dx;
                top = startRect.top + dy;
            } else if (interactionMode.startsWith('resize-')) {
                const edge = interactionMode.replace('resize-', '');
                const minEdge = this.isCoarsePointer() ? 16 : 24;

                if (edge.includes('l')) {
                    const right = startRect.left + startRect.width;
                    left = Math.min(clamped.left, right - minEdge);
                    width = right - left;
                }

                if (edge.includes('r')) {
                    width = Math.max(minEdge, clamped.left - startRect.left);
                }

                if (edge.includes('t')) {
                    const bottom = startRect.top + startRect.height;
                    top = Math.min(clamped.top, bottom - minEdge);
                    height = bottom - top;
                }

                if (edge.includes('b')) {
                    height = Math.max(minEdge, clamped.top - startRect.top);
                }
            }

            const imageRect = this.getImageOverlayRect();
            if (imageRect) {
                left = Math.max(imageRect.left, Math.min(left, imageRect.right - width));
                top = Math.max(imageRect.top, Math.min(top, imageRect.bottom - height));
                width = Math.min(width, imageRect.right - left);
                height = Math.min(height, imageRect.bottom - top);
            }

            this.updateClipScreenShades(left, top, width, height);
            lastDragRect = { left, top, width, height };
            this.autoScrollStageDuringClip(event.clientY);
        };

        const onPointerUp = (event) => {
            if (event.pointerId !== this.activePointerId) {
                return;
            }

            if (! interactionMode) {
                finishInteraction();

                return;
            }

            const rect = lastDragRect || boxStyleToOverlayRect();
            const { left, top, width, height } = rect;

            if (interactionMode === 'draw' || interactionMode === 'move' || interactionMode.startsWith('resize-')) {
                applyOverlayRect(left, top, width, height);
            } else {
                const overlay = this.normalizedToOverlayRect(this.clipNormalized);

                if (overlay) {
                    applyOverlayRect(overlay.left, overlay.top, overlay.width, overlay.height);
                }
            }

            if (interactionMode === 'draw') {
                this.exitClipDrawMode();
            }

            finishInteraction();
        };

        const onKeyDown = (event) => {
            if (event.key === 'Escape') {
                this.toggleClipMode(true);
            }
        };

        catcherEl?.addEventListener('pointerdown', onPointerDown, { passive: false });
        ui?.box?.addEventListener('pointerdown', onPointerDown, { passive: false });
        document.addEventListener('keydown', onKeyDown);

        this.clipDragCleanup = () => {
            catcherEl?.removeEventListener('pointerdown', onPointerDown);
            ui?.box?.removeEventListener('pointerdown', onPointerDown);
            document.removeEventListener('keydown', onKeyDown);
            stopDocumentTracking();
            finishInteraction();
        };
    }

    updateClipShareLinks(clipUrl, title) {
        this.updateShareLinks(this.els.clipShare, clipUrl, title, this.els.clipOpen);
    }

    updateShareLinks(container, shareUrl, title, openLink = null) {
        const encoded = encodeURIComponent(shareUrl);
        const encodedTitle = encodeURIComponent(title);
        const urls = {
            facebook: `https://www.facebook.com/sharer/sharer.php?u=${encoded}`,
            whatsapp: `https://wa.me/?text=${encodedTitle}%20${encoded}`,
            linkedin: `https://www.linkedin.com/sharing/share-offsite/?url=${encoded}`,
            x: `https://twitter.com/intent/tweet?url=${encoded}&text=${encodedTitle}`,
            email: `mailto:?subject=${encodedTitle}&body=${encodedTitle}%20${encoded}`,
        };

        container?.querySelectorAll('[data-ep-share]').forEach((link) => {
            const type = link.dataset.epShare;
            if (urls[type]) {
                link.href = urls[type];
            }
        });

        if (openLink) {
            openLink.href = shareUrl;
        }
    }

    downloadClipPreview() {
        const dataUrl = this.clipPreviewDataUrl || this.els.clipPreview?.src;

        if (! dataUrl) {
            return;
        }

        const anchor = document.createElement('a');
        anchor.href = dataUrl;
        const pageNum = this.config.clip?.page || this.currentPage;
        anchor.download = `tnf-clip-page-${pageNum}.jpg`;
        anchor.rel = 'noopener';
        document.body.appendChild(anchor);
        anchor.click();
        anchor.remove();
    }

    getClipPreviewImageUrl(clip) {
        return this.getPageUrl(clip.page)
            || this.els.pageImage?.currentSrc
            || this.els.pageImage?.src
            || null;
    }

    async renderClipPreview(clip) {
        const preview = this.els.clipPreview;
        const frame = this.els.clipPreviewFrame;
        const wrap = this.els.clipPreviewWrap;

        if (! preview || ! frame || ! wrap) {
            return;
        }

        const bitmap = await this.renderClipBitmap(clip);

        if (! bitmap) {
            wrap.classList.add('hidden');
            return;
        }

        wrap.classList.remove('hidden');
        preview.className = 'tnf-ep-clip-preview';
        preview.removeAttribute('style');
        frame.style.aspectRatio = '';
        preview.src = bitmap;
        preview.alt = `${this.config.title} — selected clip preview`;
        this.clipPreviewDataUrl = bitmap;
    }

    buildClipPreviewDataUrl(clip, pageUrl) {
        const source = this.getClipSourceElement();

        const drawFromSource = (imageLike) => {
            const naturalWidth = imageLike.naturalWidth || imageLike.width;
            const naturalHeight = imageLike.naturalHeight || imageLike.height;

            if (! naturalWidth || ! naturalHeight) {
                return;
            }

            const sx = Math.round(naturalWidth * clip.x);
            const sy = Math.round(naturalHeight * clip.y);
            const sw = Math.max(1, Math.round(naturalWidth * clip.w));
            const sh = Math.max(1, Math.round(naturalHeight * clip.h));
            const canvas = document.createElement('canvas');
            canvas.width = sw;
            canvas.height = sh;
            const ctx = canvas.getContext('2d');

            if (! ctx) {
                return;
            }

            try {
                ctx.drawImage(imageLike, sx, sy, sw, sh, 0, 0, sw, sh);
                this.clipPreviewDataUrl = canvas.toDataURL('image/jpeg', 0.9);
            } catch {
                this.clipPreviewDataUrl = '';
            }
        };

        if (source && (source.naturalWidth || source.width)) {
            drawFromSource(source);
            return;
        }

        const loader = new Image();
        loader.crossOrigin = 'anonymous';
        loader.addEventListener('load', () => drawFromSource(loader), { once: true });
        loader.src = pageUrl;
    }

    getClipSourceElement() {
        if (this.els.pageImage && ! this.els.pageImage.classList.contains('hidden')) {
            return this.els.pageImage;
        }

        if (this.els.pdfCanvas && ! this.els.pdfCanvas.classList.contains('hidden')) {
            return this.els.pdfCanvas;
        }

        return null;
    }

    isValidClip(clip) {
        return Boolean(
            clip
            && clip.page >= 1
            && clip.w > 0
            && clip.h > 0
            && clip.x >= 0
            && clip.y >= 0
            && clip.x + clip.w <= 1.001
            && clip.y + clip.h <= 1.001,
        );
    }

    buildClipUrl(clip) {
        const url = new URL(this.config.shareUrl, window.location.origin);
        url.searchParams.set('tnf_clip', '1');
        url.searchParams.set('tnf_pg', String(clip.page));
        url.searchParams.set('tnf_cx', clip.x.toFixed(4));
        url.searchParams.set('tnf_cy', clip.y.toFixed(4));
        url.searchParams.set('tnf_cw', clip.w.toFixed(4));
        url.searchParams.set('tnf_ch', clip.h.toFixed(4));

        return url.toString();
    }

    async fetchSignedClipUrl(clip) {
        if (! this.config.clipSignUrl) {
            return this.buildClipUrl(clip);
        }

        const controller = new AbortController();
        const timeout = window.setTimeout(() => controller.abort(), 5000);

        try {
            const response = await fetch(this.config.clipSignUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify(clip),
                signal: controller.signal,
            });

            if (response.ok) {
                const data = await response.json();

                if (data.url) {
                    return data.url;
                }
            }
        } catch {
            // Fall back to unsigned clip URL when signing is unavailable.
        } finally {
            window.clearTimeout(timeout);
        }

        return this.buildClipUrl(clip);
    }

    mountClipModal() {
        const modal = this.els.clipModal;

        if (! modal || modal.dataset.epMounted === '1') {
            return;
        }

        document.body.appendChild(modal);
        modal.dataset.epMounted = '1';
    }

    async openClipModal(clip) {
        this.mountClipModal();

        await this.renderClipPreview(clip);
        this.unmountClipScreen();
        document.body.classList.remove('tnf-ep-is-clipping');

        const clipUrl = await this.fetchSignedClipUrl(clip);
        this.activeClipUrl = clipUrl;

        if (this.els.clipUrl) {
            this.els.clipUrl.value = clipUrl;
        }

        this.updateClipShareLinks(clipUrl, this.config.title);
        this.els.clipModal?.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        if (! this.isCoarsePointer()) {
            this.els.clipUrl?.focus();
            this.els.clipUrl?.select();
        }
    }

    closeClipModal() {
        this.els.clipModal?.classList.add('hidden');

        if (this.els.shareModal?.classList.contains('hidden')) {
            document.body.style.overflow = '';
        }
        this.els.clipPreviewWrap?.classList.add('hidden');

        if (this.els.clipPreview) {
            this.els.clipPreview.removeAttribute('src');
            this.els.clipPreview.removeAttribute('style');
            this.els.clipPreview.className = 'tnf-ep-clip-preview';
            this.els.clipPreview.onload = null;
            this.els.clipPreview.onerror = null;
        }

        if (this.els.clipPreviewFrame) {
            this.els.clipPreviewFrame.style.aspectRatio = '';
        }

        this.clipPreviewDataUrl = '';
    }

    async renderClipBitmap(clip) {
        const clamped = {
            ...clip,
            x: clip.x,
            y: clip.y,
            w: clip.w,
            h: clip.h,
        };
        const normalized = this.clampNormalizedRect({
            x: clamped.x,
            y: clamped.y,
            w: clamped.w,
            h: clamped.h,
        });

        if (! normalized) {
            return null;
        }

        const exportClip = { ...clamped, ...normalized };
        const preferPdf = this.clipCaptureSource === 'pdf' || (! this.clipCaptureSource && this.config.pdfUrl);

        if (preferPdf) {
            if (! this.pdfDoc && this.config.pdfUrl) {
                await this.initPdfFallback();
            }

            if (this.pdfDoc) {
                const fromPdf = await this.renderClipBitmapFromPdf(exportClip);

                if (fromPdf) {
                    return fromPdf;
                }
            }
        }

        if (this.clipWorkspaceImageDataUrl) {
            const fromWorkspace = await this.renderClipBitmapFromDataUrl(exportClip, this.clipWorkspaceImageDataUrl);

            if (fromWorkspace) {
                return fromWorkspace;
            }
        }

        const pageUrl = this.getPageUrl(exportClip.page);

        if (pageUrl) {
            return this.renderClipBitmapFromImageUrl(exportClip, pageUrl);
        }

        if (! this.pdfDoc && this.config.pdfUrl) {
            await this.initPdfFallback();
        }

        if (this.pdfDoc) {
            return this.renderClipBitmapFromPdf(exportClip);
        }

        const liveSource = this.getClipSourceElement();

        if (liveSource && (liveSource.naturalWidth || liveSource.width)) {
            return this.renderClipBitmapFromImageElement(exportClip, liveSource);
        }

        return null;
    }

    async renderClipBitmapFromDataUrl(clip, dataUrl) {
        return new Promise((resolve) => {
            const img = new Image();
            img.onload = () => resolve(this.renderClipBitmapFromImageElement(clip, img));
            img.onerror = () => resolve(null);
            img.src = dataUrl;
        });
    }

    async renderClipBitmapFromImageUrl(clip, pageUrl) {
        return new Promise((resolve) => {
            const img = new Image();
            img.onload = () => resolve(this.renderClipBitmapFromImageElement(clip, img));
            img.onerror = () => resolve(null);
            img.src = pageUrl;
        });
    }

    renderClipBitmapFromImageElement(clip, imageLike) {
        const naturalWidth = imageLike.naturalWidth || imageLike.width;
        const naturalHeight = imageLike.naturalHeight || imageLike.height;

        if (! naturalWidth || ! naturalHeight) {
            return null;
        }

        const normalized = this.clampNormalizedRect(clip);

        if (! normalized) {
            return null;
        }

        const sx = Math.round(naturalWidth * normalized.x);
        const sy = Math.round(naturalHeight * normalized.y);
        const sw = Math.max(1, Math.round(naturalWidth * normalized.w));
        const sh = Math.max(1, Math.round(naturalHeight * normalized.h));
        const canvas = document.createElement('canvas');
        canvas.width = sw;
        canvas.height = sh;
        const context = canvas.getContext('2d', { alpha: false });

        if (! context) {
            return null;
        }

        context.imageSmoothingEnabled = true;
        context.imageSmoothingQuality = 'high';

        try {
            context.drawImage(imageLike, sx, sy, sw, sh, 0, 0, sw, sh);

            return canvas.toDataURL('image/jpeg', 0.94);
        } catch {
            return null;
        }
    }

    async renderClipBitmapFromPdf(clip) {
        if (! this.pdfDoc) {
            return null;
        }

        const normalized = this.clampNormalizedRect(clip);

        if (! normalized) {
            return null;
        }

        const pdfPage = await this.pdfDoc.getPage(clip.page);
        const baseViewport = pdfPage.getViewport({ scale: 1 });
        const scale = this.getClipExportScale(baseViewport.width, normalized.w);
        const renderViewport = pdfPage.getViewport({ scale });
        const canvas = document.createElement('canvas');
        canvas.width = Math.floor(renderViewport.width);
        canvas.height = Math.floor(renderViewport.height);
        const context = canvas.getContext('2d', { alpha: false });

        if (! context) {
            return null;
        }

        await pdfPage.render({ canvasContext: context, viewport: renderViewport }).promise;

        const clipCanvas = document.createElement('canvas');
        const sx = Math.round(renderViewport.width * normalized.x);
        const sy = Math.round(renderViewport.height * normalized.y);
        const sw = Math.max(1, Math.round(renderViewport.width * normalized.w));
        const sh = Math.max(1, Math.round(renderViewport.height * normalized.h));
        clipCanvas.width = sw;
        clipCanvas.height = sh;

        const clipContext = clipCanvas.getContext('2d', { alpha: false });

        if (! clipContext) {
            return null;
        }

        clipContext.imageSmoothingEnabled = true;
        clipContext.imageSmoothingQuality = 'high';
        clipContext.drawImage(canvas, sx, sy, sw, sh, 0, 0, sw, sh);

        return clipCanvas.toDataURL('image/jpeg', 0.94);
    }

    async renderClipOnly() {
        const clip = this.config.clip;

        if (! clip || ! this.els.stage || ! this.isValidClip(clip)) {
            this.showEmptyState(this.config.pdfUrl ? 'pdf' : 'empty');

            return;
        }

        // Prefer a page image crop (fast). Only open the full PDF if needed.
        const bitmap = await this.renderSharedClipBitmap(clip);

        if (! bitmap) {
            this.showEmptyState(this.config.pdfUrl ? 'pdf' : 'empty');
            return;
        }

        this.clipPreviewDataUrl = bitmap;

        const card = document.createElement('div');
        card.className = 'tnf-ep-clip-card';

        const brand = document.createElement('div');
        brand.className = 'tnf-ep-clip-card__brand';

        if (this.config.logoUrl) {
            const logo = document.createElement('img');
            logo.className = 'tnf-ep-clip-card__logo';
            logo.src = this.config.logoUrl;
            logo.alt = this.config.title || 'TNF Today';
            logo.width = 120;
            logo.height = 36;
            logo.decoding = 'async';
            logo.loading = 'eager';
            brand.appendChild(logo);
        }

        const brandText = document.createElement('div');
        brandText.className = 'tnf-ep-clip-card__text min-w-0';

        const eyebrow = document.createElement('p');
        eyebrow.className = 'tnf-ep-clip-card__eyebrow';
        eyebrow.textContent = 'Shared newspaper clip';

        const titleEl = document.createElement('p');
        titleEl.className = 'tnf-ep-clip-card__title';
        titleEl.textContent = this.config.title || '';

        brandText.appendChild(eyebrow);
        brandText.appendChild(titleEl);
        brand.appendChild(brandText);
        card.appendChild(brand);

        const viewport = document.createElement('div');
        viewport.className = 'tnf-ep-clip-viewport';

        const img = document.createElement('img');
        img.className = 'tnf-ep-clip-shared-image';
        img.src = bitmap;
        img.alt = `${this.config.title} — clip`;
        img.decoding = 'async';
        img.loading = 'eager';

        viewport.appendChild(img);
        card.appendChild(viewport);
        this.els.stage.replaceChildren(card);
        this.els.stage.scrollTop = 0;
        this.els.stage.scrollLeft = 0;

        const downloadBtn = this.root.querySelector('[data-ep-clip-page-download]');

        if (downloadBtn && ! downloadBtn.dataset.epBound) {
            downloadBtn.dataset.epBound = '1';
            downloadBtn.addEventListener('click', () => this.downloadClipPreview());
        }
    }

    async renderSharedClipBitmap(clip) {
        const normalized = this.clampNormalizedRect({
            x: clip.x,
            y: clip.y,
            w: clip.w,
            h: clip.h,
        });

        if (! normalized) {
            return null;
        }

        const exportClip = { ...clip, ...normalized };
        const pageUrl = this.getPageUrl(exportClip.page);

        if (pageUrl) {
            const fromImage = await this.renderClipBitmapFromImageUrl(exportClip, pageUrl);

            if (fromImage) {
                return fromImage;
            }
        }

        if (! this.pdfDoc && this.config.pdfUrl) {
            await this.initPdfFallback();
        }

        if (this.pdfDoc) {
            return this.renderClipBitmapFromPdf(exportClip);
        }

        return null;
    }

    async waitForPdfJs(timeoutMs = 12000) {
        const started = Date.now();

        while (typeof pdfjsLib === 'undefined' && Date.now() - started < timeoutMs) {
            await new Promise((resolve) => window.setTimeout(resolve, 50));
        }

        return typeof pdfjsLib !== 'undefined';
    }

    async initPdfFallback() {
        const pdfJsReady = await this.waitForPdfJs();

        if (! pdfJsReady) {
            console.error('TNF ePaper: PDF.js did not load.');
            return;
        }

        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        try {
            const loadingTask = pdfjsLib.getDocument({
                url: this.config.pdfUrl,
                rangeChunkSize: 65536,
                disableStream: false,
                disableAutoFetch: false,
            });
            this.pdfDoc = await loadingTask.promise;
            this.config.pageCount = this.pdfDoc.numPages;

            if (this.els.pageImage) {
                this.els.pageImage.classList.add('hidden');
            }
            if (this.els.pdfCanvas) {
                this.els.pdfCanvas.classList.remove('hidden');
            }
        } catch (error) {
            console.error('TNF ePaper: failed to open PDF.', error);
            this.pdfDoc = null;
        }
    }

    async paintPdfPage(pdfPage, baseViewport, renderScale) {
        const displayScale = this.effectiveZoom;
        const renderViewport = pdfPage.getViewport({ scale: renderScale });
        const cssWidth = baseViewport.width * displayScale;
        const cssHeight = baseViewport.height * displayScale;
        const canvas = this.els.pdfCanvas;
        const context = canvas.getContext('2d', { alpha: false });

        if (! context) {
            return;
        }

        canvas.width = renderViewport.width;
        canvas.height = renderViewport.height;
        canvas.style.width = `${cssWidth}px`;
        canvas.style.height = `${cssHeight}px`;

        this.updateStageLayout(cssWidth, cssHeight);

        await pdfPage.render({ canvasContext: context, viewport: renderViewport }).promise;
    }

    async renderPdfPage(page) {
        if (! this.pdfDoc || ! this.els.pdfCanvas) {
            return;
        }

        const pdfPage = await this.pdfDoc.getPage(page);
        const baseViewport = pdfPage.getViewport({ scale: 1 });

        this.pageWidth = baseViewport.width;
        this.pageHeight = baseViewport.height;

        this.calculateFitZoom();

        const displayScale = this.effectiveZoom;
        const pixelRatio = window.devicePixelRatio || 1;
        const isMobile = this.isCoarsePointer();
        // Keep mobile canvases lighter so finger-scroll stays smooth.
        const previewScale = Math.max(
            1,
            displayScale * Math.min(pixelRatio, isMobile ? 1.25 : 1.5) * (isMobile ? 1.05 : 1),
        );
        const finalScale = isMobile
            ? Math.max(previewScale, displayScale * Math.min(pixelRatio, 2) * 1.2)
            : Math.max(previewScale, displayScale * pixelRatio * 1.75);

        await this.paintPdfPage(pdfPage, baseViewport, previewScale);

        if (finalScale > previewScale * 1.15) {
            await this.paintPdfPage(pdfPage, baseViewport, finalScale);
        }

        void this.cachePdfThumbnail(page, pdfPage, baseViewport);
    }

    showEmptyState(reason = 'empty') {
        if (! this.els.stage) {
            return;
        }

        const messages = {
            empty: 'This edition is not available right now. Please try again later.',
            pdf: 'Could not load the newspaper. Please refresh the page or try again later.',
        };

        this.els.stage.innerHTML = `<p class="p-8 text-center text-tnf-muted">${messages[reason] || messages.empty}</p>`;
    }
}

function bootEpaperViewer() {
    const root = document.getElementById('tnf-epaper-viewer');

    if (! root) {
        return;
    }

    try {
        const config = JSON.parse(root.dataset.config || '{}');
        new TnfEpaperViewer(root, config).init();
    } catch (error) {
        console.error('TNF ePaper viewer failed to start', error);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootEpaperViewer);
} else {
    bootEpaperViewer();
}
