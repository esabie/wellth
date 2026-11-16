import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const STORAGE_PREFIX = 'wellth:bf-banner-dismissed:';

function storageKey(phase, saleDateLabel) {
    return `${STORAGE_PREFIX}${phase}:${saleDateLabel}`;
}

export default function BlackFridayBanner() {
    const blackFriday = usePage().props.black_friday;
    const [dismissed, setDismissed] = useState(true);

    useEffect(() => {
        if (!blackFriday) {
            setDismissed(true);

            return;
        }

        try {
            setDismissed(window.sessionStorage.getItem(storageKey(blackFriday.phase, blackFriday.sale_date_label)) === '1');
        } catch {
            setDismissed(false);
        }
    }, [blackFriday]);

    if (!blackFriday || dismissed) {
        return null;
    }

    const { phase, sale_date_label: saleDateLabel, discount_percent: discountPercent, promo_code: promoCode, href } =
        blackFriday;

    const message =
        phase === 'live'
            ? `Black Friday · today only · ${discountPercent}% off with code ${promoCode}`
            : `Black Friday · ${discountPercent}% off · ${saleDateLabel} · one day only`;

    const dismiss = () => {
        try {
            window.sessionStorage.setItem(storageKey(phase, saleDateLabel), '1');
        } catch {
            // Ignore private-mode / blocked storage.
        }
        setDismissed(true);
    };

    return (
        <div className="relative z-40 bg-ink text-white">
            <div className="mx-auto flex max-w-6xl items-center gap-3 px-4 py-2.5 sm:px-6">
                <p className="min-w-0 flex-1 text-center text-[11px] font-semibold tracking-[0.04em] text-white/95 sm:text-sm sm:tracking-normal">
                    <Link href={href} className="underline-offset-2 transition hover:text-punch hover:underline">
                        {message}
                    </Link>
                    <span className="hidden text-white/45 sm:inline"> · </span>
                    <Link
                        href={href}
                        className="ml-1 hidden font-bold text-punch transition hover:text-white sm:inline"
                    >
                        Shop now
                    </Link>
                </p>
                <button
                    type="button"
                    onClick={dismiss}
                    className="inline-flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-full text-white/70 transition hover:bg-white/10 hover:text-white"
                    aria-label="Dismiss Black Friday notice"
                >
                    <svg aria-hidden="true" viewBox="0 0 24 24" className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2">
                        <path d="M6 6l12 12M18 6 6 18" />
                    </svg>
                </button>
            </div>
        </div>
    );
}
