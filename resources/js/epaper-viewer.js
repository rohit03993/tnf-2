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
            clipLayer: root.querySelector('[data-ep-clip-layer]'),
            pageSelect: root.querySelector('[data-ep-page-select]'),
            pager: root.querySelector('[data-ep-pager]'),
            thumbsSidebar: root.querySelector('[data-ep-thumbs-sidebar]'),
            thumbsRail: root.querySelector('[data-ep-thumbs-rail]'),
            mobilePage: root.querySelector('[data-ep-mobile-page]'),
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
            clipScreen: document.querySelector('[data-ep-clip-screen]'),
            clipWorkspace: document.querySelector('[data-ep-clip-workspace]'),
            clipWorkspaceImage: document.querySelector('[data-ep-clip-workspace-image]'),
            clipWorkspacePage: document.querySelector('[data-ep-clip-workspace-page]'),
            clipWorkspaceScroll: document.querySelector('[data-ep-clip-workspace-scroll]'),
            clipWorkspaceCancel: document.querySelector('[data-ep-clip-workspace-cancel]'),
            clipWorkspaceShare: document.querySelector('[data-ep-clip-workspace-share]'),
            clipWorkspacePageNum: document.querySelector('[data-ep-clip-workspace-page-num]'),
            clipWorkspaceHint: document.querySelector('[data-ep-clip-workspace-hint]'),
            shareModal: root.querySelector('[data-ep-share-modal]'),
            shareUrl: root.querySelector('[data-ep-share-url]'),
            editionShare: root.querySelector('[data-ep-edition-share]'),
            shareCopyBtn: root.querySelector('[data-ep-copy-share]'),
            shareNativeBtn: root.querySelector('[data-ep-share-native]'),
            shareOpen: root.querySelector('[data-ep-share-open]'),
            clipMobileActions: root.querySelector('[data-ep-clip-mobile-actions]'),
            clipShareMobile: root.querySelector('[data-ep-clip-share-mobile]'),
            clipCancelMobile: root.querySelector('[data-ep-clip-cancel-mobile]'),
        };

        this.activeClipUrl = '';
        this.clipPreviewDataUrl = '';
        this.pendingClip = null;
        this.clipNormalized = null;
        this.clipDragCleanup = null;
        this.clipScrollHandler = null;
        this.resizeHandler = null;
        this.pdfThumbCache = {};
        this.activePointerId = null;
        this.pinchStartDistance = 0;
        this.pinchStartZoom = 1;
        this.lastTapAt = 0;
    }

    get effectiveZoom() {
        return this.fitZoom * this.userZoomFactor;
    }

    async init() {
        if (this.config.clipMode && this.config.clip) {
            await this.renderClipOnly();
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
        this.bindTouchZoom();
        this.bindResizeHandler();
        await this.setPage(this.currentPage, false);
        this.setPdfLoading(false);

        if (this.pdfDoc) {
            this.schedulePdfThumbnailPrefetch();
        }
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

    clipHintText() {
        return this.isCoarsePointer()
            ? 'Touch and drag on the newspaper to select the area you want to share'
            : 'Drag on the newspaper to highlight the section you want to share';
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
                label.textContent = `Page ${page}`;
                btn.appendChild(label);

                btn.addEventListener('click', () => this.setPage(page));
                container.appendChild(btn);
            }
        });
    }

    buildPageSelect() {
        if (! this.els.pageSelect) {
            return;
        }

        this.els.pageSelect.innerHTML = '';

        for (let page = 1; page <= this.pageCount; page++) {
            const option = document.createElement('option');
            option.value = String(page);
            option.textContent = `Page ${page} of ${this.pageCount}`;
            this.els.pageSelect.appendChild(option);
        }

        this.els.pageSelect.addEventListener('change', () => {
            this.setPage(Number(this.els.pageSelect.value));
        });
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
            if (event.key !== 'Escape') {
                return;
            }

            if (! this.els.shareModal?.classList.contains('hidden')) {
                this.closeShareModal();
            } else if (! this.els.clipModal?.classList.contains('hidden')) {
                this.closeClipModal();
            }
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

        this.els.clipCancelMobile?.addEventListener('click', () => this.toggleClipMode(true));
        this.els.clipShareMobile?.addEventListener('click', () => this.confirmClipShare());
        this.els.clipWorkspaceCancel?.addEventListener('click', () => this.toggleClipMode(true));
        this.els.clipWorkspaceShare?.addEventListener('click', () => this.confirmClipShare());
    }

    bindTouchZoom() {
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

                    this.els.pageImage.onload = onReady;
                    this.els.pageImage.onerror = resolve;
                    this.els.pageImage.src = url;

                    if (this.els.pageImage.complete && this.els.pageImage.naturalWidth) {
                        onReady();
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

        if (this.els.mobilePage) {
            this.els.mobilePage.textContent = `Page ${this.currentPage} of ${this.pageCount}`;
        }

        this.root.querySelectorAll('.tnf-ep-thumb').forEach((thumb) => {
            thumb.classList.toggle('is-active', Number(thumb.dataset.page) === this.currentPage);
        });

        this.renderPager();
        this.updateZoomLabel();

        this.root.querySelectorAll('.tnf-ep-stage-nav-btn--prev').forEach((button) => {
            button.disabled = this.currentPage <= 1;
        });
        this.root.querySelectorAll('.tnf-ep-stage-nav-btn--next').forEach((button) => {
            button.disabled = this.currentPage >= this.pageCount;
        });
    }

    renderPager() {
        if (! this.els.pager) {
            return;
        }

        this.els.pager.innerHTML = '';
        const windowSize = 5;
        let start = Math.max(1, this.currentPage - Math.floor(windowSize / 2));
        let end = Math.min(this.pageCount, start + windowSize - 1);
        start = Math.max(1, end - windowSize + 1);

        for (let page = start; page <= end; page++) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'tnf-ep-pager-btn' + (page === this.currentPage ? ' is-active' : '');
            btn.textContent = String(page);
            btn.addEventListener('click', () => this.setPage(page));
            this.els.pager.appendChild(btn);
        }
    }

    bindResizeHandler() {
        if (this.resizeHandler) {
            return;
        }

        this.resizeHandler = () => {
            window.requestAnimationFrame(() => {
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
        inner.style.transform = 'translateX(-50%)';
    }

    async applyPageZoom() {
        const width = this.pageWidth;
        const height = this.pageHeight;

        if (! width || ! height) {
            return;
        }

        this.calculateFitZoom();

        const cssWidth = width * this.effectiveZoom;
        const cssHeight = height * this.effectiveZoom;

        this.updateStageLayout(cssWidth, cssHeight);

        if (this.pdfDoc) {
            await this.renderPdfPage(this.currentPage);
        } else if (this.els.pageImage && ! this.els.pageImage.classList.contains('hidden')) {
            this.els.pageImage.style.width = `${cssWidth}px`;
            this.els.pageImage.style.height = 'auto';
        }

        this.updateZoomLabel();

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
        this.els.clipLayer?.classList.toggle('hidden', ! this.clipMode);
        this.els.clipHint?.classList.toggle('hidden', ! this.clipMode);
        this.root.querySelectorAll('[data-ep-action="clip"]').forEach((button) => {
            button.classList.toggle('is-active', this.clipMode);
            button.setAttribute('aria-pressed', this.clipMode ? 'true' : 'false');
        });

        if (this.clipMode) {
            if (! this.els.clipWorkspace || ! this.els.clipScreen) {
                console.error('TNF ePaper: clip UI is missing. Deploy the latest build (npm run build) and hard refresh.');
                this.clipMode = false;
                this.root.classList.remove('is-clip-mode');
                this.root.querySelectorAll('[data-ep-action="clip"]').forEach((button) => {
                    button.classList.remove('is-active');
                    button.setAttribute('aria-pressed', 'false');
                });
                return;
            }

            this.openClipWorkspaceShell();
            void this.prepareClipMode();
        } else {
            this.unbindClipDrag();
            this.unmountClipScreen();
            this.showClipMobileActions(false);
        }
    }

    openClipWorkspaceShell() {
        const workspace = this.els.clipWorkspace;

        if (! workspace) {
            return;
        }

        if (workspace.parentElement !== document.body) {
            document.body.appendChild(workspace);
        }

        workspace.classList.remove('hidden');
        workspace.classList.add('is-loading');
        document.body.classList.add('tnf-ep-is-clipping');
        document.body.style.overflow = 'hidden';
    }

    async prepareClipMode() {
        try {
            if (this.pdfDoc) {
                this.setPdfLoading(true);
                await this.renderPdfPage(this.currentPage);
                this.setPdfLoading(false);
            }

            const imageReady = await this.loadClipWorkspaceImage();

            if (! imageReady) {
                throw new Error('Could not capture the current page for clipping.');
            }

            this.clipWorkspaceActive = true;
            this.mountClipScreen();
            this.bindClipDrag();
            this.showClipMobileActions(false);
            this.updateClipShareButtonsState();
        } catch (error) {
            console.error('TNF ePaper: clip mode failed.', error);
            this.toggleClipMode(true);
        }
    }

    async loadClipWorkspaceImage() {
        const img = this.els.clipWorkspaceImage;

        if (! img) {
            return false;
        }

        this.fitPageToView();
        await this.applyPageZoom();

        const dataUrl = await this.captureCurrentPagePreview();

        if (! dataUrl) {
            return false;
        }

        this.clipWorkspaceImageDataUrl = dataUrl;
        img.src = dataUrl;

        await new Promise((resolve, reject) => {
            img.onload = () => resolve();
            img.onerror = () => reject(new Error('Clip workspace image failed to load'));
        });

        return img.naturalWidth > 0 && img.naturalHeight > 0;
    }

    async captureCurrentPagePreview() {
        const pageImage = this.els.pageImage;
        const pdfCanvas = this.els.pdfCanvas;

        if (pageImage && ! pageImage.classList.contains('hidden') && pageImage.src) {
            if (pageImage.complete && pageImage.naturalWidth) {
                return pageImage.src;
            }

            await new Promise((resolve, reject) => {
                pageImage.addEventListener('load', resolve, { once: true });
                pageImage.addEventListener('error', reject, { once: true });
            });

            return pageImage.src;
        }

        if (this.pdfDoc) {
            return this.renderPdfClipPreviewDataUrl(this.currentPage);
        }

        if (pdfCanvas && ! pdfCanvas.classList.contains('hidden') && pdfCanvas.width > 0) {
            try {
                return pdfCanvas.toDataURL('image/jpeg', 0.92);
            } catch (error) {
                console.warn('TNF ePaper: display canvas capture failed.', error);
            }
        }

        return null;
    }

    async renderPdfClipPreviewDataUrl(pageNum) {
        if (! this.pdfDoc) {
            return null;
        }

        const pdfPage = await this.pdfDoc.getPage(pageNum);
        const baseViewport = pdfPage.getViewport({ scale: 1 });
        const maxWidth = 1200;
        const scale = Math.min(1.5, maxWidth / baseViewport.width);
        const viewport = pdfPage.getViewport({ scale });
        const canvas = document.createElement('canvas');
        canvas.width = Math.floor(viewport.width);
        canvas.height = Math.floor(viewport.height);

        const context = canvas.getContext('2d', { alpha: false });

        if (! context) {
            return null;
        }

        await pdfPage.render({ canvasContext: context, viewport }).promise;

        return canvas.toDataURL('image/jpeg', 0.9);
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
        if (this.clipWorkspaceActive) {
            const img = this.els.clipWorkspaceImage;

            if (! img || img.clientWidth < 1 || img.clientHeight < 1) {
                return null;
            }

            return {
                left: 0,
                top: 0,
                width: img.clientWidth,
                height: img.clientHeight,
                right: img.clientWidth,
                bottom: img.clientHeight,
            };
        }

        const imageRect = this.getImageScreenRect();
        const reference = this.els.stage?.getBoundingClientRect() || this.getStageWrapRect();

        if (! imageRect || imageRect.width < 1 || imageRect.height < 1) {
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
        if (this.clipWorkspaceActive && this.els.clipWorkspacePage) {
            const page = this.els.clipWorkspacePage;

            return {
                width: page.clientWidth,
                height: page.clientHeight,
                offsetLeft: 0,
                offsetTop: 0,
            };
        }

        const stage = this.els.stage;
        const wrap = this.els.stageWrap;

        if (! stage || ! wrap) {
            return {
                width: window.innerWidth,
                height: window.innerHeight,
                offsetLeft: 0,
                offsetTop: 0,
            };
        }

        const stageRect = stage.getBoundingClientRect();
        const wrapRect = wrap.getBoundingClientRect();

        return {
            width: stage.clientWidth,
            height: stage.clientHeight,
            offsetLeft: stageRect.left - wrapRect.left,
            offsetTop: stageRect.top - wrapRect.top,
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
        const minSize = this.isCoarsePointer() ? 18 : 24;

        if (clipWidth < minSize || clipHeight < minSize) {
            return null;
        }

        return {
            x: (clipLeft - imageRect.left) / imageRect.width,
            y: (clipTop - imageRect.top) / imageRect.height,
            w: clipWidth / imageRect.width,
            h: clipHeight / imageRect.height,
        };
    }

    normalizedToClip(normalized) {
        if (! normalized) {
            return null;
        }

        return {
            page: this.currentPage,
            x: normalized.x,
            y: normalized.y,
            w: normalized.w,
            h: normalized.h,
        };
    }

    getDefaultClipNormalized() {
        if (this.isCoarsePointer()) {
            return { x: 0.04, y: 0.06, w: 0.92, h: 0.38 };
        }

        return { x: 0.3, y: 0.3, w: 0.4, h: 0.4 };
    }

    showClipMobileActions(show) {
        if (! this.els.clipMobileActions) {
            return;
        }

        this.els.clipMobileActions.classList.toggle('hidden', ! show);
        this.els.clipMobileActions.setAttribute('aria-hidden', show ? 'false' : 'true');
    }

    updateClipShareButtonsState() {
        const ready = Boolean(this.pendingClip);

        if (this.els.clipShareMobile) {
            this.els.clipShareMobile.disabled = ! ready;
            this.els.clipShareMobile.classList.toggle('is-ready', ready);
        }

        if (this.els.clipWorkspaceShare) {
            this.els.clipWorkspaceShare.disabled = ! ready;
            this.els.clipWorkspaceShare.classList.toggle('is-ready', ready);
        }
    }

    mountClipScreen() {
        if (! this.els.clipScreen || ! this.els.clipWorkspace) {
            return;
        }

        this.els.clipWorkspace.classList.remove('hidden', 'is-loading');
        this.els.clipScreen.classList.remove('hidden');
        this.els.clipScreen.setAttribute('aria-hidden', 'false');

        if (this.els.clipWorkspacePageNum) {
            this.els.clipWorkspacePageNum.textContent = String(this.currentPage);
        }

        this.pendingClip = null;
        this.clipNormalized = this.getDefaultClipNormalized();

        const hint = this.els.clipScreen.querySelector('[data-ep-clip-hint-text]');
        if (hint) {
            hint.textContent = this.isCoarsePointer()
                ? 'Drag to select an area, then tap Share clip'
                : 'Drag to select an area, resize the box, then click Share';
        }

        if (this.els.clipWorkspaceHint) {
            this.els.clipWorkspaceHint.textContent = this.isCoarsePointer()
                ? 'Touch and drag on the page to select what you want to share'
                : 'Click and drag on the page to select what you want to share';
        }

        const ui = this.getClipScreenElements();
        ui?.toolbar?.classList.add('hidden');
        ui?.box?.classList.remove('is-complete');

        this.scheduleClipOverlaySync(true);

        const source = this.getClipSourceElement();
        if (source && ! (source.complete && (source.naturalWidth || source.width))) {
            source.addEventListener('load', () => this.scheduleClipOverlaySync(true), { once: true });
        }

        if (! this.clipScrollHandler) {
            this.clipScrollHandler = () => this.syncClipOverlay();
            this.els.clipWorkspaceScroll?.addEventListener('scroll', this.clipScrollHandler, { passive: true });
            window.addEventListener('resize', this.clipScrollHandler, { passive: true });
        }
    }

    unmountClipScreen() {
        this.els.clipWorkspace?.classList.add('hidden');
        this.els.clipWorkspace?.classList.remove('is-loading');
        this.els.clipScreen?.classList.add('hidden');
        this.els.clipScreen?.setAttribute('aria-hidden', 'true');
        this.clipWorkspaceActive = false;
        document.body.classList.remove('tnf-ep-is-clipping');
        document.body.style.overflow = '';
        this.activePointerId = null;
        this.resetClipScreenSelection();
        this.updateClipShareButtonsState();

        if (this.clipScrollHandler) {
            this.els.clipWorkspaceScroll?.removeEventListener('scroll', this.clipScrollHandler);
            window.removeEventListener('resize', this.clipScrollHandler);
            this.clipScrollHandler = null;
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
        if (ui?.sizeLabel) {
            ui.sizeLabel.textContent = `${Math.round(overlay.width)} × ${Math.round(overlay.height)}`;
        }

        const clip = this.normalizedToClip(this.clipNormalized);

        if (clip) {
            this.pendingClip = clip;
            ui?.box?.classList.add('is-complete');
            ui?.toolbar?.classList.remove('hidden');
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
            toolbar: visual.querySelector('[data-ep-clip-toolbar]'),
            sizeLabel: visual.querySelector('.tnf-ep-clip-size'),
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
        ui.toolbar?.classList.add('hidden');

        this.updateClipScreenShades(0, 0, 0, 0);
        ui.box?.classList.remove('is-complete');
        ui.visual?.classList.remove('is-active');

        if (ui.sizeLabel) {
            ui.sizeLabel.textContent = '';
        }

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
        const reference = this.clipWorkspaceActive
            ? this.els.clipWorkspacePage?.getBoundingClientRect()
            : this.els.stage?.getBoundingClientRect() || this.getStageWrapRect();

        if (! reference) {
            return { x: clientX, y: clientY };
        }

        return {
            x: clientX - reference.left,
            y: clientY - reference.top,
        };
    }

    bindClipDrag() {
        const catcherEl = this.els.clipScreen?.querySelector('[data-ep-clip-catcher]');
        const ui = this.getClipScreenElements();
        let interactionMode = null;
        let dragStart = null;
        let startRect = null;

        const applyOverlayRect = (left, top, width, height) => {
            const normalized = this.overlayRectToNormalized(left, top, width, height);
            const hint = this.els.clipScreen?.querySelector('[data-ep-clip-hint-text]');

            if (! normalized) {
                if (hint) {
                    hint.textContent = 'Selection too small — try again';
                }

                return false;
            }

            this.clipNormalized = normalized;
            this.syncClipOverlay(true);

            if (hint) {
                hint.textContent = 'Resize the box or drag a new area, then tap Share';
            }

            return true;
        };

        const finishInteraction = () => {
            interactionMode = null;
            dragStart = null;
            startRect = null;
            this.activePointerId = null;
        };

        const onPointerDown = (event) => {
            if (this.activePointerId !== null) {
                return;
            }

            const handle = event.target.closest('[data-ep-clip-handle]');
            const box = event.target.closest('.tnf-ep-clip-box');
            const toolbar = event.target.closest('[data-ep-clip-toolbar]');

            if (toolbar) {
                return;
            }

            if (! this.isPointInsideImage(event.clientX, event.clientY)) {
                return;
            }

            event.preventDefault();
            this.activePointerId = event.pointerId;

            const target = event.currentTarget;
            try {
                target.setPointerCapture(event.pointerId);
            } catch {
                // Pointer capture is optional on some browsers.
            }

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

                if (edge.includes('l')) {
                    const right = startRect.left + startRect.width;
                    left = Math.min(clamped.left, right - 24);
                    width = right - left;
                }

                if (edge.includes('r')) {
                    width = Math.max(24, clamped.left - startRect.left);
                }

                if (edge.includes('t')) {
                    const bottom = startRect.top + startRect.height;
                    top = Math.min(clamped.top, bottom - 24);
                    height = bottom - top;
                }

                if (edge.includes('b')) {
                    height = Math.max(24, clamped.top - startRect.top);
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

            const elements = this.getClipScreenElements();
            if (elements?.sizeLabel) {
                elements.sizeLabel.textContent = `${Math.round(width)} × ${Math.round(height)}`;
            }
        };

        const onPointerUp = (event) => {
            if (event.pointerId !== this.activePointerId) {
                return;
            }

            try {
                event.currentTarget.releasePointerCapture(event.pointerId);
            } catch {
                // Ignore release errors.
            }

            if (! interactionMode) {
                finishInteraction();
                return;
            }

            const overlay = this.normalizedToOverlayRect(this.clipNormalized);
            const elements = this.getClipScreenElements();
            const boxStyle = elements?.box?.style;
            const left = Number.parseFloat(boxStyle?.left || '0');
            const top = Number.parseFloat(boxStyle?.top || '0');
            const width = Number.parseFloat(boxStyle?.width || '0');
            const height = Number.parseFloat(boxStyle?.height || '0');

            if (interactionMode === 'draw' || interactionMode === 'move' || interactionMode.startsWith('resize-')) {
                applyOverlayRect(left, top, width, height);
            } else if (overlay) {
                applyOverlayRect(overlay.left, overlay.top, overlay.width, overlay.height);
            }

            finishInteraction();
        };

        const onKeyDown = (event) => {
            if (event.key === 'Escape') {
                this.toggleClipMode(true);
            }
        };

        const onCancel = (event) => {
            event.preventDefault();
            event.stopPropagation();
            this.toggleClipMode(true);
        };

        const onShare = (event) => {
            event.preventDefault();
            event.stopPropagation();
            this.confirmClipShare();
        };

        const cancelBtn = this.els.clipScreen?.querySelector('[data-ep-clip-cancel]');
        const shareBtn = this.els.clipScreen?.querySelector('[data-ep-clip-share]');

        catcherEl?.addEventListener('pointerdown', onPointerDown);
        catcherEl?.addEventListener('pointermove', onPointerMove);
        catcherEl?.addEventListener('pointerup', onPointerUp);
        catcherEl?.addEventListener('pointercancel', onPointerUp);
        ui?.box?.addEventListener('pointerdown', onPointerDown);
        ui?.box?.addEventListener('pointermove', onPointerMove);
        ui?.box?.addEventListener('pointerup', onPointerUp);
        ui?.box?.addEventListener('pointercancel', onPointerUp);
        cancelBtn?.addEventListener('click', onCancel);
        shareBtn?.addEventListener('click', onShare);
        document.addEventListener('keydown', onKeyDown);

        this.clipDragCleanup = () => {
            catcherEl?.removeEventListener('pointerdown', onPointerDown);
            catcherEl?.removeEventListener('pointermove', onPointerMove);
            catcherEl?.removeEventListener('pointerup', onPointerUp);
            catcherEl?.removeEventListener('pointercancel', onPointerUp);
            ui?.box?.removeEventListener('pointerdown', onPointerDown);
            ui?.box?.removeEventListener('pointermove', onPointerMove);
            ui?.box?.removeEventListener('pointerup', onPointerUp);
            ui?.box?.removeEventListener('pointercancel', onPointerUp);
            cancelBtn?.removeEventListener('click', onCancel);
            shareBtn?.removeEventListener('click', onShare);
            document.removeEventListener('keydown', onKeyDown);
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
        if (this.clipWorkspaceActive && this.els.clipWorkspaceImage?.src) {
            return this.els.clipWorkspaceImage;
        }

        if (this.els.pageImage && ! this.els.pageImage.classList.contains('hidden')) {
            return this.els.pageImage;
        }

        if (this.els.pdfCanvas && ! this.els.pdfCanvas.classList.contains('hidden')) {
            return this.els.pdfCanvas;
        }

        return null;
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
        const pageUrl = this.getPageUrl(clip.page);

        if (pageUrl) {
            return this.renderClipBitmapFromImageUrl(clip, pageUrl);
        }

        if (! this.pdfDoc && this.config.pdfUrl) {
            await this.initPdfFallback();
        }

        if (this.pdfDoc) {
            return this.renderClipBitmapFromPdf(clip);
        }

        const liveSource = this.getClipSourceElement();

        if (liveSource && (liveSource.naturalWidth || liveSource.width)) {
            return this.renderClipBitmapFromImageElement(clip, liveSource);
        }

        return null;
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

        const sx = Math.round(naturalWidth * clip.x);
        const sy = Math.round(naturalHeight * clip.y);
        const sw = Math.max(1, Math.round(naturalWidth * clip.w));
        const sh = Math.max(1, Math.round(naturalHeight * clip.h));
        const canvas = document.createElement('canvas');
        canvas.width = sw;
        canvas.height = sh;
        const context = canvas.getContext('2d');

        if (! context) {
            return null;
        }

        try {
            context.drawImage(imageLike, sx, sy, sw, sh, 0, 0, sw, sh);
            return canvas.toDataURL('image/jpeg', 0.92);
        } catch {
            return null;
        }
    }

    async renderClipBitmapFromPdf(clip) {
        if (! this.pdfDoc) {
            return null;
        }

        const pdfPage = await this.pdfDoc.getPage(clip.page);
        const pixelRatio = window.devicePixelRatio || 1;
        const renderViewport = pdfPage.getViewport({ scale: Math.max(2, pixelRatio * 2) });
        const canvas = document.createElement('canvas');
        canvas.width = renderViewport.width;
        canvas.height = renderViewport.height;
        const context = canvas.getContext('2d', { alpha: false });

        if (! context) {
            return null;
        }

        await pdfPage.render({ canvasContext: context, viewport: renderViewport }).promise;

        const clipCanvas = document.createElement('canvas');
        const sx = Math.round(renderViewport.width * clip.x);
        const sy = Math.round(renderViewport.height * clip.y);
        const sw = Math.max(1, Math.round(renderViewport.width * clip.w));
        const sh = Math.max(1, Math.round(renderViewport.height * clip.h));
        clipCanvas.width = sw;
        clipCanvas.height = sh;

        const clipContext = clipCanvas.getContext('2d', { alpha: false });

        if (! clipContext) {
            return null;
        }

        clipContext.drawImage(canvas, sx, sy, sw, sh, 0, 0, sw, sh);

        return clipCanvas.toDataURL('image/jpeg', 0.92);
    }

    async renderClipOnly() {
        const clip = this.config.clip;

        if (! clip || ! this.els.stage) {
            return;
        }

        const bitmap = await this.renderClipBitmap(clip);

        if (! bitmap) {
            this.showEmptyState(this.config.pdfUrl ? 'pdf' : 'empty');
            return;
        }

        this.clipPreviewDataUrl = bitmap;

        const viewport = document.createElement('div');
        viewport.className = 'tnf-ep-clip-viewport tnf-container';

        const img = document.createElement('img');
        img.className = 'tnf-ep-clip-shared-image';
        img.src = bitmap;
        img.alt = `${this.config.title} — clip`;

        viewport.appendChild(img);
        this.els.stage.replaceChildren(viewport);

        const downloadBtn = this.root.querySelector('[data-ep-clip-page-download]');

        if (downloadBtn && ! downloadBtn.dataset.epBound) {
            downloadBtn.dataset.epBound = '1';
            downloadBtn.addEventListener('click', () => this.downloadClipPreview());
        }
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
        const mobileBoost = this.isCoarsePointer() ? 1.35 : 1;
        const previewScale = Math.max(1, displayScale * Math.min(pixelRatio, 1.5) * mobileBoost);
        const finalScale = Math.max(previewScale, displayScale * pixelRatio * 1.75);

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
            empty: 'This edition has no pages yet. Upload a PDF in admin under Content → ePaper Editions.',
            pdf: 'The PDF could not be loaded. Check that storage is linked (<code>php artisan storage:link</code>) and the PDF file exists in storage.',
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
