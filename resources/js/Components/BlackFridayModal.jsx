import { Link, usePage } from '@inertiajs/react';
import { useEffect, useId, useRef, useState } from 'react';
import { createPortal } from 'react-dom';

const STORAGE_PREFIX = 'wellth:bf-modal-seen:';

function storageKey(saleDateLabel) {
    return `${STORAGE_PREFIX}${saleDateLabel}`;
}

export default function BlackFridayModal() {
    const blackFriday = usePage().props.black_friday;
    const [open, setOpen] = useState(false);
    const titleId = useId();
    const descriptionId = useId();
    const dialogRef = useRef(null);
    const canDismissRef = useRef(false);
    const blackFridayRef = useRef(blackFriday);

    useEffect(() => {
        blackFridayRef.current = blackFriday;
    }, [blackFriday]);

    const markSeenAndClose = () => {
        const campaign = blackFridayRef.current;

        if (campaign) {
            try {
                window.sessionStorage.setItem(storageKey(campaign.sale_date_label), '1');
            } catch {
                // Ignore private-mode / blocked storage.
            }
        }

        setOpen(false);
    };

    useEffect(() => {
        if (!blackFriday) {
            setOpen(false);

            return undefined;
        }

        let alreadySeen = false;

        try {
            alreadySeen = window.sessionStorage.getItem(storageKey(blackFriday.sale_date_label)) === '1';
        } catch {
            alreadySeen = false;
        }

        if (alreadySeen) {
            setOpen(false);

            return undefined;
        }

        const showTimer = window.setTimeout(() => {
            setOpen(true);
        }, 400);

        return () => {
            window.clearTimeout(showTimer);
        };
    }, [blackFriday]);

    useEffect(() => {
        if (!open) {
            return undefined;
        }

        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        canDismissRef.current = false;
        dialogRef.current?.focus({ preventScroll: true });

        const dismissTimer = window.setTimeout(() => {
            canDismissRef.current = true;
        }, 350);

        const onKeyDown = (event) => {
            if (event.key === 'Escape' && canDismissRef.current) {
                markSeenAndClose();
            }
        };

        window.addEventListener('keydown', onKeyDown);

        return () => {
            window.clearTimeout(dismissTimer);
            document.body.style.overflow = previousOverflow;
            window.removeEventListener('keydown', onKeyDown);
        };
    }, [open]);

    if (!open || !blackFriday || typeof document === 'undefined') {
        return null;
    }

    const { phase, sale_date_label: saleDateLabel, discount_percent: discountPercent = 10, href } = blackFriday;
    const isLive = phase === 'live';

    return createPortal(
        <div className="fixed inset-0 z-[60] flex h-dvh items-center justify-center p-4">
            <button
                type="button"
                aria-label="Close Black Friday notice"
                className="absolute inset-0 bg-ink/45"
                onClick={() => {
                    if (canDismissRef.current) {
                        markSeenAndClose();
                    }
                }}
            />

            <div
                ref={dialogRef}
                role="dialog"
                tabIndex={-1}
                aria-modal="true"
                aria-labelledby={titleId}
                aria-describedby={descriptionId}
                className="relative w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-[0_24px_80px_-24px_rgba(26,26,26,0.45)] outline-none sm:max-w-lg"
            >
                <div className="bg-punch px-6 py-5 text-white sm:px-8 sm:py-6">
                    <p className="text-[11px] font-bold tracking-[0.18em] text-white/80 uppercase">Wellth Foods</p>
                    <h2 id={titleId} className="mt-2 font-display text-3xl font-extrabold tracking-tight sm:text-4xl">
                        {isLive ? 'Black Friday is today.' : 'Black Friday Weekend is coming.'}
                    </h2>
                    <p className="mt-3 inline-flex rounded-full bg-white px-3 py-1 text-[11px] font-extrabold tracking-[0.14em] text-punch uppercase">
                        {isLive ? 'Today only' : 'One day only'} · {discountPercent}% off
                    </p>
                </div>

                <div className="px-6 py-6 sm:px-8 sm:py-7">
                    <p id={descriptionId} className="text-sm leading-relaxed text-ink/75 sm:text-base">
                        {isLive ? (
                            <>
                                Take <strong className="font-bold text-ink">{discountPercent}% off</strong> wellness shots and
                                drinks — today only, {saleDateLabel}.
                            </>
                        ) : (
                            <>
                                Mark your calendar for <strong className="font-bold text-ink">{saleDateLabel}. Black Friday Weekend</strong>.
                                <strong className="font-bold text-ink">{discountPercent}% off</strong> the
                                Wellth lineup.
                            </>
                        )}
                    </p>

                    <div className="mt-6 flex justify-end">
                        <Link
                            href={href || '/shop'}
                            onClick={markSeenAndClose}
                            className="inline-flex min-h-11 cursor-pointer items-center justify-center rounded-full bg-ink px-5 py-2.5 text-sm font-extrabold text-white outline-none transition hover:bg-leaf-800 focus-visible:ring-2 focus-visible:ring-forest/40 focus-visible:ring-offset-2"
                        >
                            {isLive ? 'Shop the sale' : 'Browse the shop'}
                        </Link>
                    </div>
                </div>
            </div>
        </div>,
        document.body,
    );
}
