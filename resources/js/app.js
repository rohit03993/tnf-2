import './bootstrap';

import Alpine from 'alpinejs';
import { registerTnfSite, initSiteUi } from './site';

registerTnfSite(Alpine);

window.Alpine = Alpine;

Alpine.start();

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSiteUi);
} else {
    initSiteUi();
}
