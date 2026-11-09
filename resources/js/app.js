import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, Fragment, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import SeoHead from './Components/SeoHead.vue';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const sendAnalyticsEvent = (name, parameters = {}) => {
    if (typeof window.gtag === 'function') window.gtag('event', name, parameters);
};

router.on('finish', () => {
    if (typeof window.gtag === 'function') {
        window.gtag('event', 'page_view', {
            page_location: window.location.href,
            page_path: window.location.pathname,
            page_title: document.title,
        });
    }
});

document.addEventListener('click', (event) => {
    const target = event.target.closest('a,button');
    if (!target || typeof window.gtag !== 'function') return;
    const href = target.getAttribute('href') || '';
    const label = target.textContent?.trim().slice(0, 100) || '';
    let eventName = target.dataset.gaEvent;
    if (!eventName && href.startsWith('/pertandingan/')) eventName = 'view_match';
    if (!eventName && href === '/jadwal') eventName = 'view_schedule';
    if (!eventName && href.startsWith('/news/')) eventName = 'view_news';
    if (!eventName && /share|bagikan/i.test(label)) eventName = href.includes('/news') ? 'share_article' : 'share_match';
    if (!eventName && /play video|our story/i.test(label)) eventName = 'play_our_story';
    if (eventName) sendAnalyticsEvent(eventName, { link_url: href, link_text: label });
});

createInertiaApp({
    title: (title) =>
        !title
            ? appName
            : title.includes('| COSMIC') || title.endsWith(appName)
            ? title
            : `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({
            render: () =>
                h(Fragment, null, [h(App, props), h(SeoHead)]),
        })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
