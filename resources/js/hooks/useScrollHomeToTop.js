import { useLayoutEffect } from 'react';

function jumpToTop() {
    window.scrollTo(0, 0);
    document.documentElement.scrollTop = 0;
    document.body.scrollTop = 0;
}

/**
 * Force the homepage to the top on mount/refresh.
 * Browsers often restore scroll after paint, so we keep nudging until it sticks.
 */
export default function useScrollHomeToTop() {
    useLayoutEffect(() => {
        if ('scrollRestoration' in window.history) {
            window.history.scrollRestoration = 'manual';
        }

        jumpToTop();

        let attempts = 0;
        let frame = 0;
        let stopped = false;

        const forceTop = () => {
            if (stopped) {
                return;
            }

            jumpToTop();
            attempts += 1;

            if (window.scrollY > 0 && attempts < 40) {
                frame = requestAnimationFrame(forceTop);

                return;
            }

            if (attempts < 12) {
                frame = requestAnimationFrame(forceTop);
            }
        };

        frame = requestAnimationFrame(forceTop);

        const timeouts = [0, 16, 50, 100, 250, 500, 1000].map((ms) =>
            window.setTimeout(jumpToTop, ms),
        );

        const onLoad = () => jumpToTop();
        const onPageshow = (event) => {
            if (event.persisted) {
                jumpToTop();
            }
        };

        window.addEventListener('load', onLoad);
        window.addEventListener('pageshow', onPageshow);

        return () => {
            stopped = true;
            cancelAnimationFrame(frame);
            timeouts.forEach((id) => window.clearTimeout(id));
            window.removeEventListener('load', onLoad);
            window.removeEventListener('pageshow', onPageshow);
        };
    }, []);
}
