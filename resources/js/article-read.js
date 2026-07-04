export function initArticleRead() {
    const tracker = document.getElementById('tnf-article-read-tracker');

    if (! tracker) {
        return;
    }

    const readUrl = tracker.dataset.readUrl;

    if (! readUrl) {
        return;
    }

    const countEl = tracker.querySelector('[data-article-readers]');
    const labelEl = tracker.querySelector('[data-article-readers-label]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    let recorded = false;

    const recordRead = () => {
        if (recorded) {
            return;
        }

        recorded = true;

        fetch(readUrl, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
            },
            credentials: 'same-origin',
        })
            .then((response) => (response.ok ? response.json() : null))
            .then((payload) => {
                if (! payload || typeof payload.readers_count !== 'number') {
                    return;
                }

                if (countEl) {
                    countEl.textContent = payload.readers_label ?? String(payload.readers_count);
                }

                if (labelEl) {
                    labelEl.textContent = payload.readers_count === 1 ? 'reader' : 'readers';
                }
            })
            .catch(() => {});
    };

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                observer.disconnect();
                window.setTimeout(recordRead, 1200);
            }
        }, { threshold: 0.2 });

        observer.observe(tracker);
    } else {
        window.setTimeout(recordRead, 1200);
    }
}
