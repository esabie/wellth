import { usePage } from '@inertiajs/react';
import { useEffect, useId, useRef, useState } from 'react';
import { createPortal } from 'react-dom';

export function useDeliveryBreak(onContinue) {
    const cartCount = usePage().props.cart?.count ?? 0;
    const [open, setOpen] = useState(false);

    const requestAdd = () => {
        if (cartCount > 0) {
            onContinue();

            return;
        }

        setOpen(true);
    };

    const modal = (
        <DeliveryBreakModal
            open={open}
            onCancel={() => setOpen(false)}
            onContinue={() => {
                setOpen(false);
                onContinue();
            }}
        />
    );

    return { requestAdd, modal };
}

export default function DeliveryBreakModal({ open, onCancel, onContinue }) {
    const titleId = useId();
    const descriptionId = useId();
    const dialogRef = useRef(null);
    const onCancelRef = useRef(onCancel);
    const canDismissRef = useRef(false);

    useEffect(() => {
        onCancelRef.current = onCancel;
    }, [onCancel]);

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
                onCancelRef.current();
            }
        };

        window.addEventListener('keydown', onKeyDown);

        return () => {
            window.clearTimeout(dismissTimer);
            document.body.style.overflow = previousOverflow;
            window.removeEventListener('keydown', onKeyDown);
        };
    }, [open]);

    if (!open || typeof document === 'undefined') {
        return null;
    }

    return createPortal(
        <div className="fixed inset-0 z-[60] flex h-dvh items-center justify-center p-4">
            <button
                type="button"
                aria-label="Cancel"
                className="absolute inset-0 bg-ink/40"
                onClick={() => {
                    if (canDismissRef.current) {
                        onCancel();
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
                className="relative w-full max-w-md rounded-3xl bg-white p-6 shadow-[0_24px_80px_-24px_rgba(26,26,26,0.45)] outline-none sm:p-8"
            >
                <p className="text-[11px] font-bold tracking-[0.16em] text-forest/55 uppercase">
                    WELLTH FOODS
                </p>
                <h2 id={titleId} className="mt-2 font-display text-2xl font-semibold tracking-tight text-ink">
                    Delivery Notice
                </h2>
                <p id={descriptionId} className="mt-3 text-sm leading-relaxed text-ink/70">
                    Our next consignment is expected to be delivered on 16 November 2026. You can proceed with your order by clicking <strong className="font-bold text-ink">Continue</strong> below.
                </p>

                <p className="mt-3 text-sm leading-relaxed text-ink/70">
                    We appreciate your patience and look forward to serving you!


                </p>

                <div className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        onClick={onCancel}
                        className="min-h-11 cursor-pointer rounded-full border border-black/10 px-5 py-2.5 text-sm font-bold text-ink outline-none transition hover:bg-mist focus-visible:ring-2 focus-visible:ring-forest/40 focus-visible:ring-offset-2"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        onClick={onContinue}
                        className="min-h-11 cursor-pointer rounded-full bg-ink px-5 py-2.5 text-sm font-extrabold text-white outline-none transition hover:bg-leaf-800 focus-visible:ring-2 focus-visible:ring-forest/40 focus-visible:ring-offset-2"
                    >
                        Continue
                    </button>
                </div>
            </div>
        </div>,
        document.body,
    );
}
