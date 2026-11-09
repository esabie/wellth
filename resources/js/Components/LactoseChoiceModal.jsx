import { useEffect, useId, useRef, useState } from 'react';
import { createPortal } from 'react-dom';

export function productRequiresLactoseChoice(slug) {
    return slug === 'cloudy-carrot';
}

/**
 * Ask for lactose preference before continuing (Cloudy Carrot only).
 */
export function useLactoseChoice(onContinue) {
    const [open, setOpen] = useState(false);

    const requestChoice = () => setOpen(true);

    const modal = (
        <LactoseChoiceModal
            open={open}
            onCancel={() => setOpen(false)}
            onContinue={(lactoseIntolerant) => {
                setOpen(false);
                onContinue(lactoseIntolerant);
            }}
        />
    );

    return { requestChoice, modal };
}

export default function LactoseChoiceModal({ open, onCancel, onContinue }) {
    const titleId = useId();
    const descriptionId = useId();
    const dialogRef = useRef(null);
    const onCancelRef = useRef(onCancel);
    const canDismissRef = useRef(false);
    const [choice, setChoice] = useState(null);

    useEffect(() => {
        onCancelRef.current = onCancel;
    }, [onCancel]);

    useEffect(() => {
        if (!open) {
            setChoice(null);

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
                <p className="text-[11px] font-bold tracking-[0.16em] text-forest/55 uppercase">Cloudy Carrot</p>
                <h2 id={titleId} className="mt-2 font-display text-2xl font-semibold tracking-tight text-ink">
                    Lactose preference
                </h2>
                <p id={descriptionId} className="mt-3 text-sm leading-relaxed text-ink/70">
                    Cloudy Carrot is a milky drink. Please indicate if you prefer lactose free milk so we prepare your
                    bottles the right way.
                </p>

                <fieldset className="mt-5 space-y-2">
                    <legend className="sr-only">Are you lactose intolerant?</legend>
                    {[
                        { value: true, label: 'Yes, I am lactose intolerant' },
                        { value: false, label: 'No, I am not lactose intolerant' },
                    ].map((option) => {
                        const selected = choice === option.value;

                        return (
                            <button
                                key={String(option.value)}
                                type="button"
                                onClick={() => setChoice(option.value)}
                                aria-pressed={selected}
                                className={`flex min-h-12 w-full cursor-pointer items-center rounded-2xl border px-4 py-3 text-left text-sm font-semibold transition ${
                                    selected
                                        ? 'border-forest bg-leaf-50 text-ink'
                                        : 'border-black/10 bg-white text-ink/80 hover:border-black/20 hover:bg-mist'
                                }`}
                            >
                                <span
                                    className={`mr-3 flex h-4 w-4 shrink-0 items-center justify-center rounded-full border ${
                                        selected ? 'border-forest' : 'border-black/25'
                                    }`}
                                    aria-hidden="true"
                                >
                                    {selected && <span className="h-2 w-2 rounded-full bg-forest" />}
                                </span>
                                {option.label}
                            </button>
                        );
                    })}
                </fieldset>

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
                        disabled={choice === null}
                        onClick={() => onContinue(choice)}
                        className="min-h-11 cursor-pointer rounded-full bg-ink px-5 py-2.5 text-sm font-extrabold text-white outline-none transition hover:bg-leaf-800 focus-visible:ring-2 focus-visible:ring-forest/40 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Continue
                    </button>
                </div>
            </div>
        </div>,
        document.body,
    );
}
