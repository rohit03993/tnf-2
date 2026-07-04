function readCookie(name) {
    const escaped = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const match = document.cookie.match(new RegExp(`(?:^|; )${escaped}=([^;]*)`));

    return match ? decodeURIComponent(match[1]) : null;
}

function buildJsonHeaders() {
    const headers = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    const xsrfToken = readCookie('XSRF-TOKEN');

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

function parseCount(value) {
    const parsed = parseInt(String(value ?? '0').replace(/,/g, ''), 10);

    return Number.isFinite(parsed) ? parsed : 0;
}

export function initArticleRead() {
    const engagement = document.getElementById('tnf-article-engagement');

    if (! engagement) {
        return;
    }

    const readUrl = engagement.dataset.readUrl;
    const likeUrl = engagement.dataset.likeUrl;

    const countEl = engagement.querySelector('[data-article-readers]');
    const labelEl = engagement.querySelector('[data-article-readers-label]');
    const likesEl = engagement.querySelector('[data-article-likes]');
    const likeBtn = engagement.querySelector('[data-article-like]');
    const likeLabelEl = likeBtn?.querySelector('.tnf-article-like__label');

    let recorded = false;
    let liking = false;

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

    const postEngagement = (url) => fetch(url, {
        method: 'POST',
        headers: buildJsonHeaders(),
        credentials: 'same-origin',
    }).then(async (response) => {
        if (! response.ok) {
            throw new Error(`Request failed (${response.status})`);
        }

        return response.json();
    });

    const recordRead = () => {
        if (recorded || ! readUrl) {
            return;
        }

        recorded = true;

        postEngagement(readUrl)
            .then(updateEngagement)
            .catch(() => {
                recorded = false;
            });
    };

    const toggleLike = () => {
        if (liking || ! likeUrl || ! likeBtn) {
            return;
        }

        const wasLiked = likeBtn.dataset.liked === 'true';
        const previousCount = parseCount(likesEl?.textContent);
        const optimisticCount = wasLiked
            ? Math.max(0, previousCount - 1)
            : previousCount + 1;

        liking = true;
        likeBtn.classList.add('tnf-article-like--busy');

        updateEngagement({
            liked: ! wasLiked,
            likes_count: optimisticCount,
            likes_label: String(optimisticCount),
        });

        postEngagement(likeUrl)
            .then((payload) => {
                updateEngagement(payload);

                if (! recorded && readUrl) {
                    recorded = true;
                }
            })
            .catch(() => {
                updateEngagement({
                    liked: wasLiked,
                    likes_count: previousCount,
                    likes_label: String(previousCount),
                });
            })
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
