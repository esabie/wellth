import { Link, usePage } from '@inertiajs/react';

export default function BlackFridayBanner() {
    const blackFriday = usePage().props.black_friday;

    if (!blackFriday) {
        return null;
    }

    const { phase, sale_date_label: saleDateLabel, href } = blackFriday;

    return (
        <div className="relative z-40 bg-punch text-white">
            <div className="mx-auto flex max-w-6xl items-center justify-center px-4 py-2.5 sm:px-6">
                <Link
                    href={href}
                    className="inline-flex flex-wrap items-center justify-center gap-x-2 gap-y-1 text-center text-sm font-bold tracking-[0.04em] text-white transition hover:opacity-90 sm:gap-x-3 sm:text-base sm:tracking-normal"
                >
                    {phase === 'live' ? (
                        <>
                            <span>Black Friday</span>
                            <span aria-hidden="true" className="text-white/55">
                                ·
                            </span>
                            <span className="rounded-full bg-white px-2.5 py-0.5 text-[11px] font-extrabold tracking-[0.14em] text-punch uppercase sm:text-xs">
                                Today only
                            </span>
                        </>
                    ) : (
                        <>
                            <span>Black Friday Sale is coming soon</span>
                            <span aria-hidden="true" className="text-white/55">
                                ·
                            </span>
                            <span className="font-extrabold tracking-wide">{saleDateLabel}</span>
                            <span aria-hidden="true" className="text-white/55">
                                ·
                            </span>
                            <span className="rounded-full bg-white px-2.5 py-0.5 text-[11px] font-extrabold tracking-[0.14em] text-punch uppercase sm:text-xs">
                                10% OFF
                            </span>
                        </>
                    )}
                </Link>
            </div>
        </div>
    );
}
