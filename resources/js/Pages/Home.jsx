import { Head, Link } from '@inertiajs/react';
import ShopLayout from '../Layouts/ShopLayout';

export default function Home({ formulas }) {
    return (
        <ShopLayout overlayHeader>
            <Head title="Organic wellness shots" />

            <section className="relative isolate min-h-[88vh] overflow-hidden bg-[#efebe4]">
                <img
                    src="/images/hero-shots.jpg"
                    alt="Wellth organic wellness shots on stone"
                    className="absolute inset-0 -z-10 h-full w-full object-cover object-[70%_center] sm:object-[75%_center] lg:object-right"
                    decoding="async"
                    fetchPriority="high"
                />

                <div className="mx-auto flex min-h-[88vh] max-w-6xl items-center px-4 pb-20 pt-28 sm:px-6">
                    <div className="max-w-xl space-y-6">
                        <h1 className="font-display text-5xl leading-[0.95] font-extrabold tracking-tight text-forest sm:text-6xl lg:text-7xl">
                            a little bottle of Wellth.
                        </h1>
                        <p className="max-w-md text-base text-forest/80 sm:text-lg">
                            Wellness in a shot. Bold flavors, clean nutrition, zero additives.
                        </p>
                        <div className="flex flex-wrap items-center gap-3 pt-1">
                            <Link
                                href="/shop"
                                className="rounded-full bg-ink px-6 py-3 text-sm font-bold text-white transition hover:bg-leaf-800"
                            >
                                Shop now
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <section id="formulas" className="bg-[#f3efe6] px-4 py-16 sm:px-6 sm:py-20">
                <div className="mx-auto max-w-6xl">
                    <p className="text-xs font-bold tracking-[0.2em] text-forest uppercase">
                        Find your formula
                    </p>
                    <h2 className="mt-3 max-w-3xl font-serif text-3xl font-bold tracking-tight text-forest sm:text-4xl lg:text-5xl">
                        Choose The Support That Fits Your Day.
                    </h2>
                    <p className="mt-4 max-w-2xl text-base text-forest/80 sm:text-lg">
                        Five purposeful organic shots. Choose your support and explore the full details.
                    </p>

                    <div className="mt-12 grid auto-rows-fr gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                        {formulas.map((formula) => (
                            <Link
                                key={formula.slug}
                                href={formula.href}
                                className="group flex h-full flex-col overflow-hidden rounded-2xl bg-white shadow-[0_10px_40px_-12px_rgba(31,61,42,0.28)] ring-1 ring-black/[0.04] transition duration-300 hover:-translate-y-1.5 hover:shadow-[0_22px_50px_-14px_rgba(31,61,42,0.35)]"
                            >
                                <div className="relative aspect-square shrink-0 overflow-hidden">
                                    <img
                                        src={formula.image}
                                        alt={`${formula.name} shots with ingredients`}
                                        className="h-full w-full object-cover transition duration-700 ease-out group-hover:scale-105"
                                        loading="lazy"
                                    />
                                    <div className="pointer-events-none absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-black/10 to-transparent" />
                                </div>

                                <div className="flex min-h-0 flex-1 flex-col px-4 pt-3.5 pb-4">
                                    <h3 className="min-h-[2.4rem] font-serif text-[1.25rem] leading-tight font-bold text-forest">
                                        {formula.name}
                                    </h3>

                                    <ul className="mt-3 min-h-[6.5rem] space-y-1.5 border-t border-forest/10 pt-3 text-sm text-forest/70">
                                        {formula.points.map((point) => (
                                            <li key={point} className="flex items-center gap-2.5">
                                                <span
                                                    aria-hidden="true"
                                                    className="h-1.5 w-1.5 shrink-0 rounded-full bg-punch/80"
                                                />
                                                <span className="line-clamp-1">{point}</span>
                                            </li>
                                        ))}
                                    </ul>

                                    <div className="mt-auto pt-4">
                                        <span className="inline-flex w-full items-center justify-center rounded-xl bg-punch px-4 py-2.5 text-sm font-bold text-white shadow-[0_8px_20px_-8px_rgba(232,93,42,0.7)] transition group-hover:bg-[#d44f1f]">
                                            View details
                                        </span>
                                    </div>
                                </div>
                            </Link>
                        ))}
                    </div>
                </div>
            </section>

            <section className="bg-[#efebe4] px-4 py-16 sm:px-6 sm:py-20">
                <div className="mx-auto grid max-w-6xl gap-10 lg:grid-cols-2 lg:items-center">
                    <div className="space-y-4">
                        <p className="text-xs font-bold tracking-[0.18em] text-ink/50 uppercase">Your daily ritual</p>
                        <h2 className="font-display text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">
                            Function without the theatre.
                        </h2>
                        <p className="max-w-md text-ink/70">
                            Clear purpose. Bold ingredients. Genuinely enjoyable flavour. That&apos;s how wellness should
                            feel.
                        </p>
                        <Link
                            href="/shop"
                            className="inline-flex rounded-full bg-ink px-6 py-3 text-sm font-bold text-white transition hover:bg-leaf-800"
                        >
                            Discover Wellth
                        </Link>
                    </div>
                    <div className="rounded-[2rem] bg-white/70 p-8 ring-1 ring-black/5">
                        <p className="font-display text-2xl font-extrabold text-ink">Pick your blends.</p>
                        <p className="mt-2 text-ink/65">
                            Sold by the bottle. Mix formulas and raise the quantity on any shot you want more of.
                        </p>
                        <ul className="mt-6 space-y-2 text-sm font-medium text-ink/80">
                            <li>Ginja</li>
                            <li>Glow Getter</li>
                            <li>Ruby Rush</li>
                            <li>Crave Control</li>
                            <li>Belly Reset</li>
                        </ul>
                        <Link
                            href="/shop"
                            className="mt-8 inline-flex rounded-full border border-ink/15 px-5 py-2.5 text-sm font-bold text-ink transition hover:border-ink/40"
                        >
                            Shop bottles
                        </Link>
                    </div>
                </div>
            </section>

        </ShopLayout>
    );
}
