import '../css/app.css';
import { createInertiaApp, router } from '@inertiajs/react';
import PageLoader from './Components/PageLoader';

if (typeof window !== 'undefined') {
    if ('scrollRestoration' in window.history) {
        window.history.scrollRestoration = 'manual';
    }

    const isHome = () => window.location.pathname === '/' || window.location.pathname === '';

    const scrollHomeToTop = () => {
        if (!isHome()) {
            return;
        }

        window.scrollTo(0, 0);
        document.documentElement.scrollTop = 0;
        document.body.scrollTop = 0;
    };

    scrollHomeToTop();

    window.addEventListener('pageshow', scrollHomeToTop);
    window.addEventListener('load', () => {
        scrollHomeToTop();
        requestAnimationFrame(() => {
            scrollHomeToTop();
            requestAnimationFrame(scrollHomeToTop);
        });
    });

    router.on('navigate', (event) => {
        if (event.detail.page.component === 'Home') {
            scrollHomeToTop();
        }
    });
}

createInertiaApp({
    progress: false,
    withApp: (app) => (
        <>
            <PageLoader />
            {app}
        </>
    ),
});
