export function initArticleRead() {
    const engagement = document.getElementById('tnf-article-engagement');

    if (! engagement) {
        return;
    }

    const readUrl = engagement.dataset.readUrl;
    const likeUrl = engagement.dataset.likeUrl;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const countEl = engagement.querySelector('[data-article-readers]');
    const labelEl = engagement.querySelector('[data-article-readers-label]');
    const likesEl = engagement.querySelector('[data-article-likes]');
    const likeBtn = engagement.querySelector('[data-article-like]');
    const likeLabelEl = likeBtn?.querySelector('.tnf-article-like__label');

    let recorded = false;
    let liking = false;

    const headers = () => ({
        Accept: 'application/json',
        'Content-Type': 'application/json',
        ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
    });

    const updateEngagement = (payload) => {
        if (! payload) {
            return;
        }

        if (typeof payload.readers_count === 'number' && countEl) {
            countEl.textContent = payload.readers_label ?? String(payload.readers_count);
        }

        if (labelEl && typeof payload.readers_count === 'number') {
            labelEl.textContent = payload.readers_count === 1 ? 'reader' : 'readers';
        }

        if (typeof payload.likes_count === 'number' && likesEl) {
            likesEl.textContent = payload.likes_label ?? String(payload.likes_count);
        }

        if (typeof payload.liked === 'boolean' && likeBtn) {
            likeBtn.dataset.liked = payload.liked ? 'true' : 'false';
            likeBtn.setAttribute('aria-pressed', payload.liked ? 'true' : 'false');
            likeBtn.setAttribute('aria-label', payload.liked ? 'Unlike this story' : 'Like this story');
            likeBtn.classList.toggle('tnf-article-like--active', payload.liked);

            if (likeLabelEl) {
                likeLabelEl.textContent = payload.liked ? 'Liked' : 'Like';
            }
        }
    };

    const recordRead = () => {
        if (recorded || ! readUrl) {
            return;
        }

        recorded = true;

        fetch(readUrl, {
            method: 'POST',
            headers: headers(),
            credentials: 'same-origin',
        })
            .then((response) => (response.ok ? response.json() : null))
            .then(updateEngagement)
            .catch(() => {});
    };

    const toggleLike = () => {
        if (liking || ! likeUrl || ! likeBtn) {
            return;
        }

        liking = true;
        likeBtn.classList.add('tnf-article-like--busy');

        fetch(likeUrl, {
            method: 'POST',
            headers: headers(),
            credentials: 'same-origin',
        })
            .then((response) => (response.ok ? response.json() : null))
            .then((payload) => {
                updateEngagement(payload);

                if (! recorded && readUrl) {
                    recorded = true;
                }
            })
            .catch(() => {})
            .finally(() => {
                liking = false;
                likeBtn.classList.remove('tnf-article-like--busy');
            });
    };

    likeBtn?.addEventListener('click', toggleLike);

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                observer.disconnect();
                window.setTimeout(recordRead, 1200);
            }
        }, { threshold: 0.2 });

        observer.observe(engagement);
    } else {
        window.setTimeout(recordRead, 1200);
    }
}
