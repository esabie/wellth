import { useEffect, useId, useRef, useState } from 'react';
import { createPortal } from 'react-dom';

const EXIT_MS = 280;
const AUTO_CLOSE_MS = 4000;

export default function PaymentProofReceivedModal({ open, onClose, phone }) {
    const titleId = useId();
    const descriptionId = useId();
    const dialogRef = useRef(null);
    const onCloseRef = useRef(onClose);
    const canDismissRef = useRef(false);
    const [rendered, setRendered] = useState(open);
    const [leaving, setLeaving] = useState(false);

    useEffect(() => {
        onCloseRef.current = onClose;
    }, [onClose]);

    useEffect(() => {
        if (open) {
            setRendered(true);
            setLeaving(false);

            return undefined;
        }

        if (!rendered) {
            return undefined;
        }

        setLeaving(true);
        const unmountTimer = window.setTimeout(() => {
            setRendered(false);
            setLeaving(false);
        }, EXIT_MS);

        return () => {
            window.clearTimeout(unmountTimer);
        };
    }, [open, rendered]);

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

        const autoCloseTimer = window.setTimeout(() => {
            onCloseRef.current();
        }, AUTO_CLOSE_MS);

        const onKeyDown = (event) => {
            if (event.key === 'Escape' && canDismissRef.current) {
                onCloseRef.current();
            }
        };

        window.addEventListener('keydown', onKeyDown);

        return () => {
            window.clearTimeout(dismissTimer);
            window.clearTimeout(autoCloseTimer);
            document.body.style.overflow = previousOverflow;
            window.removeEventListener('keydown', onKeyDown);
        };
    }, [open]);

    if (!rendered || typeof document === 'undefined') {
        return null;
    }

    return createPortal(
        <div className="fixed inset-0 z-[60] flex h-dvh items-center justify-center p-4">
            <button
                type="button"
                aria-label="Close"
                className={`portal-modal-backdrop absolute inset-0 bg-ink/40${leaving ? ' is-leaving' : ''}`}
                onClick={() => {
                    if (canDismissRef.current) {
                        onClose();
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
                className={`portal-modal-panel relative w-full max-w-md rounded-3xl bg-white p-6 shadow-[0_24px_80px_-24px_rgba(26,26,26,0.45)] outline-none sm:p-8${leaving ? ' is-leaving' : ''}`}
            >
                <div className="portal-modal-icon flex h-12 w-12 items-center justify-center rounded-full bg-leaf-100 text-leaf-800">
                    <svg viewBox="0 0 24 24" className="h-6 w-6" fill="none" aria-hidden="true">
                        <path
                            className="portal-modal-check"
                            d="M5 13l4 4L19 7"
                            stroke="currentColor"
                            strokeWidth="2.5"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        />
                    </svg>
                </div>

                <p className="mt-5 text-[11px] font-bold tracking-[0.16em] text-forest/55 uppercase">
                    Upload successful
                </p>
                <h2 id={titleId} className="mt-2 font-display text-2xl font-semibold tracking-tight text-ink">
                    Payment screenshot received
                </h2>
                <p id={descriptionId} className="mt-3 text-sm leading-relaxed text-ink/70">
                    We&apos;ve got your proof of payment. We&apos;ll confirm it shortly
                    {phone ? (
                        <>
                            {' '}
                            and text <span className="font-semibold text-ink">{phone}</span> once it&apos;s approved
                        </>
                    ) : null}
                    .
                </p>

                <div className="mt-6 flex justify-end">
                    <button
                        type="button"
                        onClick={onClose}
                        className="min-h-11 cursor-pointer rounded-full bg-ink px-5 py-2.5 text-sm font-extrabold text-white outline-none transition hover:bg-leaf-800 focus-visible:ring-2 focus-visible:ring-forest/40 focus-visible:ring-offset-2"
                    >
                        Got it
                    </button>
                </div>
            </div>
        </div>,
        document.body,
    );
}
