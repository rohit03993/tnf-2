import './bootstrap';

import Alpine from 'alpinejs';
import { registerTnfSite, initSiteUi } from './site';
import { initPwaInstall } from './pwa-install';

registerTnfSite(Alpine);

window.Alpine = Alpine;

Alpine.start();

function bootSite() {
    initSiteUi();
    initPwaInstall();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootSite);
} else {
    bootSite();
}
