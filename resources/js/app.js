import './bootstrap';

import Alpine from 'alpinejs';
import { initArticleRead } from './article-read';
import { registerTnfSite, initSiteUi } from './site';
import { initPwaInstall } from './pwa-install';

registerTnfSite(Alpine);

window.Alpine = Alpine;

Alpine.start();

function bootSite() {
    initSiteUi();
    initPwaInstall();
    initArticleRead();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootSite);
} else {
    bootSite();
}
