import { Head, Link } from '@inertiajs/react';
import HeroSlider from '../Components/HeroSlider';
import Reveal from '../Components/Reveal';
import useScrollHomeToTop from '../hooks/useScrollHomeToTop';
import ShopLayout from '../Layouts/ShopLayout';

export default function Home({ formulas, bouquet }) {
    useScrollHomeToTop();

    return (
        <ShopLayout overlayHeader>
            <Head title="Organic wellness shots" />

            <section className="relative isolate min-h-dvh overflow-hidden bg-[#f4efe7] sm:min-h-[92vh]">
                <HeroSlider />

                <div className="relative mx-auto flex min-h-dvh max-w-6xl items-end px-4 pb-20 pt-24 sm:min-h-[92vh] sm:items-center sm:px-6 sm:pb-20 sm:pt-32 lg:items-center">
                    <div className="hero-copy w-full max-w-lg space-y-5 sm:space-y-6 lg:max-w-xl">
                        <p className="text-[0.7rem] font-bold tracking-[0.28em] text-forest/55 uppercase">
                            Wellth
                        </p>
                        <h1 className="font-display text-4xl leading-[0.94] font-extrabold tracking-tight text-forest sm:text-5xl lg:text-7xl">
                            a little bottle of Wellth.
                        </h1>
                        <p className="max-w-md text-base leading-relaxed text-forest/75 sm:text-lg">
                            Organic wellness shots with bold flavor and clean ingredients, made for your daily ritual.
                        </p>
                        <div className="flex w-full flex-wrap items-center justify-center gap-3 pt-1 sm:justify-start">
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

            <section id="formulas" className="relative overflow-hidden bg-[#f3efe6] px-4 py-16 sm:px-6 sm:py-24">
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute -top-24 right-[-10%] h-72 w-72 rounded-full bg-punch/10 blur-3xl"
                />
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute bottom-0 left-[-8%] h-80 w-80 rounded-full bg-forest/10 blur-3xl"
                />

                <div className="relative mx-auto max-w-6xl">
                    <Reveal variant="drama">
                        <p className="text-xs font-bold tracking-[0.2em] text-forest uppercase">
                            Find your formula
                        </p>
                    </Reveal>
                    <Reveal variant="drama" delay={120}>
                        <h2 className="mt-3 max-w-3xl font-display text-3xl font-extrabold tracking-tight text-forest sm:text-4xl lg:text-5xl">
                            Choose the support that fits your day.
                        </h2>
                    </Reveal>
                    <Reveal variant="rise" delay={220}>
                        <p className="mt-4 max-w-2xl text-base text-forest/80 sm:text-lg">
                            Five purposeful organic shots. Choose your support and explore the full details.
                        </p>
                    </Reveal>

                    <div className="mt-12 grid auto-rows-fr gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                        {formulas.map((formula, formulaIndex) => (
                            <Reveal
                                key={formula.slug}
                                variant="card"
                                delay={140 + formulaIndex * 90}
                                className="h-full"
                            >
                                <Link
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
                                        <h3 className="font-display text-lg leading-tight font-bold tracking-tight text-forest sm:min-h-[2.4rem]">
                                            {formula.name}
                                        </h3>

                                        <ul className="mt-3 space-y-1.5 border-t border-forest/10 pt-3 text-sm text-forest/70 sm:min-h-[6.5rem]">
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
                            </Reveal>
                        ))}
                    </div>
                </div>
            </section>

            <section className="relative overflow-hidden bg-[#efebe4] px-4 py-16 sm:px-6 sm:py-24">
                <div className="relative mx-auto grid max-w-6xl gap-10 lg:grid-cols-2 lg:items-center">
                    <Reveal variant="slide-left" className="space-y-4">
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
                    </Reveal>
                    {bouquet && (
                        <Reveal variant="slide-right" delay={160}>
                            <div className="rounded-[2rem] bg-white/70 p-5 ring-1 ring-black/5 sm:p-8">
                                <p className="font-display text-2xl font-extrabold text-ink">The whole lineup, one box.</p>
                                <p className="mt-2 text-ink/65">
                                    {bouquet.blurb || 'The Wellth bouquet — a ready-made mixed box. No building or choosing required.'}
                                </p>
                                <ul className="mt-6 space-y-2 text-sm font-medium text-ink/80">
                                    {bouquet.contents.map((line) => (
                                        <li key={line}>{line}</li>
                                    ))}
                                </ul>
                                <Link
                                    href={bouquet.href}
                                    className="mt-8 inline-flex rounded-full border border-ink/15 px-5 py-2.5 text-sm font-bold text-ink transition hover:border-ink/40"
                                >
                                    Shop the bouquet
                                </Link>
                            </div>
                        </Reveal>
                    )}
                </div>
            </section>

        </ShopLayout>
    );
}
