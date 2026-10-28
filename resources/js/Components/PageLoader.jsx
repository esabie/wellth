import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

const SHOW_DELAY_MS = 120;
const MIN_VISIBLE_MS = 280;

export default function PageLoader() {
    const [visible, setVisible] = useState(false);
    const [leaving, setLeaving] = useState(false);
    const showTimer = useRef(null);
    const shownAt = useRef(0);
    const hideTimer = useRef(null);
    const activeVisits = useRef(0);

    useEffect(() => {
        const boot = document.getElementById('wellth-boot-loader');

        if (!boot) {
            return undefined;
        }

        const revealTimer = window.setTimeout(() => {
            boot.classList.add('is-done');
            window.setTimeout(() => boot.remove(), 420);
        }, 180);

        return () => window.clearTimeout(revealTimer);
    }, []);

    useEffect(() => {
        const clearTimers = () => {
            if (showTimer.current) {
                window.clearTimeout(showTimer.current);
                showTimer.current = null;
            }

            if (hideTimer.current) {
                window.clearTimeout(hideTimer.current);
                hideTimer.current = null;
            }
        };

        const show = () => {
            clearTimers();
            shownAt.current = Date.now();
            setLeaving(false);
            setVisible(true);
        };

        const hide = () => {
            clearTimers();

            const elapsed = Date.now() - shownAt.current;
            const wait = Math.max(0, MIN_VISIBLE_MS - elapsed);

            hideTimer.current = window.setTimeout(() => {
                setLeaving(true);
                hideTimer.current = window.setTimeout(() => {
                    setVisible(false);
                    setLeaving(false);
                }, 320);
            }, wait);
        };

        const onStart = (event) => {
            if (event.detail.visit.prefetch) {
                return;
            }

            activeVisits.current += 1;
            clearTimers();
            showTimer.current = window.setTimeout(() => {
                if (activeVisits.current > 0) {
                    show();
                }
            }, SHOW_DELAY_MS);
        };

        const onFinish = (event) => {
            if (event.detail.visit.prefetch) {
                return;
            }

            activeVisits.current = Math.max(0, activeVisits.current - 1);

            if (activeVisits.current === 0) {
                hide();
            }
        };

        const removeStart = router.on('start', onStart);
        const removeFinish = router.on('finish', onFinish);
        const removeError = router.on('error', onFinish);
        const removeHttpException = router.on('httpException', onFinish);
        const removeNetworkError = router.on('networkError', onFinish);

        return () => {
            clearTimers();
            removeStart();
            removeFinish();
            removeError();
            removeHttpException();
            removeNetworkError();
        };
    }, []);

    if (!visible) {
        return null;
    }

    return (
        <div
            className={`page-loader${leaving ? ' is-leaving' : ''}`}
            role="status"
            aria-live="polite"
            aria-busy="true"
            aria-label="Loading"
        >
            <div className="page-loader-bar" aria-hidden="true">
                <span />
            </div>
        </div>
    );
}
