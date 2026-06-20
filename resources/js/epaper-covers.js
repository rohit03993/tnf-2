/**
 * Renders ePaper archive card covers from PDF page 1 when no cover image exists.
 */

const pdfDocCache = new Map();

async function waitForPdfJs(timeoutMs = 12000) {
    const started = Date.now();

    while (typeof pdfjsLib === 'undefined' && Date.now() - started < timeoutMs) {
        await new Promise((resolve) => window.setTimeout(resolve, 50));
    }

    return typeof pdfjsLib !== 'undefined';
}

function waitForLayout() {
    return new Promise((resolve) => {
        requestAnimationFrame(() => requestAnimationFrame(resolve));
    });
}

function getPdfDocument(url) {
    if (! pdfDocCache.has(url)) {
        pdfDocCache.set(
            url,
            pdfjsLib.getDocument({
                url,
                rangeChunkSize: 65536,
                disableStream: false,
                disableAutoFetch: false,
            }).promise,
        );
    }

    return pdfDocCache.get(url);
}

async function renderPdfCover(element) {
    const pdfUrl = element.dataset.pdfUrl;

    if (! pdfUrl || element.dataset.epCoverReady === '1') {
        return;
    }

    element.dataset.epCoverReady = '1';

    await waitForLayout();

    const pdf = await getPdfDocument(pdfUrl);
    const page = await pdf.getPage(1);
    const baseViewport = page.getViewport({ scale: 1 });
    const frame = element.closest('.tnf-epaper-card-cover-frame') ?? element.parentElement;
    const boxWidth = frame?.clientWidth || element.clientWidth || 280;
    const targetAspect = 3 / 4;
    const pixelRatio = Math.min(window.devicePixelRatio || 1, 1.5);
    const outputWidth = Math.max(1, Math.round(boxWidth * pixelRatio));
    const outputHeight = Math.max(1, Math.round(boxWidth / targetAspect * pixelRatio));
    const coverScale = Math.max(
        outputWidth / baseViewport.width,
        outputHeight / baseViewport.height,
    );
    const viewport = page.getViewport({ scale: coverScale });
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d', { alpha: false });

    if (! context) {
        return;
    }

    canvas.width = outputWidth;
    canvas.height = outputHeight;

    const offsetX = (viewport.width - outputWidth) / 2;
    const offsetY = (viewport.height - outputHeight) / 2;

    await page.render({
        canvasContext: context,
        viewport,
        transform: [1, 0, 0, 1, -offsetX, -offsetY],
    }).promise;

    const img = document.createElement('img');
    img.src = canvas.toDataURL('image/jpeg', 0.82);
    img.alt = element.getAttribute('aria-label') || 'ePaper cover';
    img.loading = 'lazy';
    img.className = 'tnf-epaper-card-cover';
    img.decoding = 'async';

    element.replaceWith(img);
}

async function bootEpaperCovers() {
    const targets = [...document.querySelectorAll('[data-ep-pdf-cover]')];

    if (! targets.length) {
        return;
    }

    const ready = await waitForPdfJs();

    if (! ready) {
        return;
    }

    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    if (! ('IntersectionObserver' in window)) {
        for (const element of targets) {
            try {
                await renderPdfCover(element);
            } catch (error) {
                console.error('TNF ePaper cover failed', error);
            }
        }

        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (! entry.isIntersecting) {
                return;
            }

            observer.unobserve(entry.target);

            renderPdfCover(entry.target).catch((error) => {
                console.error('TNF ePaper cover failed', error);
            });
        });
    }, {
        rootMargin: '120px 0px',
        threshold: 0.01,
    });

    targets.forEach((element) => observer.observe(element));
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootEpaperCovers);
} else {
    bootEpaperCovers();
}
