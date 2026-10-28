import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import ShopLayout from '../../Layouts/ShopLayout';
import { cartMutationOptions } from '../../lib/cartVisit';

function formatMoney(value) {
    return new Intl.NumberFormat('en-GH', {
        style: 'currency',
        currency: 'GHS',
    }).format(Number(value));
}

export default function Show({ formula }) {
    const [adding, setAdding] = useState(false);

    const addToCart = () => {
        if (!formula.product_id) {
            return;
        }

        setAdding(true);
        router.post('/cart', { product_id: formula.product_id, quantity: 1 }, {
            ...cartMutationOptions,
            onFinish: () => setAdding(false),
        });
    };

    return (
        <ShopLayout>
            <Head title={formula.name} />

            <section className="mx-auto max-w-6xl px-4 pt-4 pb-28 sm:px-6 sm:pb-16 sm:pt-5">
                <Link
                    href="/shop"
                    className="text-sm font-medium text-forest/60 transition hover:text-forest"
                >
                    ← Formulas
                </Link>

                <div className="mt-6 grid items-start gap-10 lg:grid-cols-12 lg:gap-12">
                    <div className="lg:col-span-6">
                        <img
                            src={formula.image}
                            alt={formula.name}
                            className="aspect-[4/5] w-full rounded-2xl object-cover sm:aspect-square"
                        />

                        {formula.ingredients?.length > 0 && (
                            <div className="mt-8">
                                <p className="text-[11px] font-bold tracking-[0.18em] text-forest/45 uppercase">
                                    What&apos;s inside
                                </p>
                                <div className="mt-4 grid grid-cols-2 gap-x-4 gap-y-5 sm:grid-cols-4">
                                    {formula.ingredients.map((ingredient) => (
                                        <figure key={ingredient.name}>
                                            <div className="aspect-square overflow-hidden rounded-xl bg-[#ebe6dc]">
                                                {ingredient.image ? (
                                                    <img
                                                        src={ingredient.image}
                                                        alt={ingredient.name}
                                                        className="h-full w-full object-cover"
                                                    />
                                                ) : (
                                                    <div className="flex h-full items-center justify-center px-2 text-center text-xs font-medium text-ink/40">
                                                        {ingredient.name}
                                                    </div>
                                                )}
                                            </div>
                                            <figcaption className="mt-2 text-center text-xs font-medium text-forest/80">
                                                {ingredient.name}
                                            </figcaption>
                                        </figure>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>

                    <div className="lg:col-span-6 lg:pt-2">
                        <h1 className="font-serif text-4xl leading-[1.05] font-bold tracking-tight text-forest sm:text-5xl">
                            {formula.name}
                        </h1>

                        <p className="mt-4 flex flex-wrap items-baseline gap-x-2 gap-y-1">
                            <span className="font-display text-2xl font-extrabold tracking-tight text-ink">
                                {formatMoney(formula.price)}
                            </span>
                            <span className="text-sm text-ink/50">
                                per bottle{formula.bottle_size ? ` · ${formula.bottle_size}` : ''}
                            </span>
                        </p>

                        <div className="mt-7 max-w-xl space-y-3">
                            <p className="text-lg leading-snug font-medium text-forest">
                                {formula.blurb}
                            </p>
                            <p className="text-[15px] leading-relaxed text-ink/65">
                                {formula.description}
                            </p>
                        </div>

                        {formula.highlights?.length > 0 && (
                            <div className="mt-9 border-t border-forest/12 pt-7">
                                <h2 className="font-serif text-xl font-bold text-forest">
                                    Why you&apos;ll love it
                                </h2>
                                <ul className="mt-4 space-y-2.5">
                                    {formula.highlights.map((highlight) => (
                                        <li
                                            key={highlight}
                                            className="flex gap-3 text-[15px] leading-snug text-ink/75"
                                        >
                                            <span
                                                aria-hidden="true"
                                                className="mt-[0.45em] h-1 w-1 shrink-0 rounded-full bg-punch"
                                            />
                                            <span>{highlight}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}

                        {(formula.taste || formula.ritual) && (
                            <dl className="mt-8 grid gap-6 border-t border-forest/12 pt-7 sm:grid-cols-2">
                                {formula.taste && (
                                    <div>
                                        <dt className="text-[11px] font-bold tracking-[0.16em] text-forest/45 uppercase">
                                            Taste
                                        </dt>
                                        <dd className="mt-2 text-[15px] leading-relaxed text-forest/85">
                                            {formula.taste}
                                        </dd>
                                    </div>
                                )}
                                {formula.ritual && (
                                    <div>
                                        <dt className="text-[11px] font-bold tracking-[0.16em] text-forest/45 uppercase">
                                            Daily ritual
                                        </dt>
                                        <dd className="mt-2 text-[15px] leading-relaxed text-forest/85">
                                            {formula.ritual}
                                        </dd>
                                    </div>
                                )}
                            </dl>
                        )}

                        <div className="mt-9 hidden border-t border-forest/12 pt-7 lg:block">
                            <button
                                type="button"
                                onClick={addToCart}
                                disabled={adding || !formula.product_id}
                                className="flex w-full cursor-pointer items-center justify-center gap-2.5 rounded-2xl bg-ink px-6 py-3.5 text-sm font-semibold text-white shadow-[0_10px_24px_-12px_rgba(31,61,42,0.55)] transition hover:bg-leaf-800 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <svg
                                    aria-hidden="true"
                                    viewBox="0 0 24 24"
                                    className="h-[18px] w-[18px]"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="1.8"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                >
                                    <path d="M6 7h12l-1.2 11.2a2 2 0 0 1-2 1.8H9.2a2 2 0 0 1-2-1.8L6 7Z" />
                                    <path d="M9 7V5.8A3 3 0 0 1 12 2.8 3 3 0 0 1 15 5.8V7" />
                                </svg>
                                <span>{adding ? 'Adding…' : 'Add to Cart'}</span>
                            </button>

                            <p className="mt-4 max-w-md text-sm leading-relaxed text-ink/55">
                                Made fresh to order. Allow up to one week. Minimum 10 bottles, mix formulas freely.
                                Shipping will be communicated when your order is about to be dispatched.
                            </p>
                        </div>

                        <p className="mt-9 max-w-md border-t border-forest/12 pt-7 text-sm leading-relaxed text-ink/55 lg:hidden">
                            Made fresh to order. Allow up to one week. Minimum 10 bottles, mix formulas freely.
                            Shipping will be communicated when your order is about to be dispatched.
                        </p>
                    </div>
                </div>
            </section>

            <div className="fixed inset-x-0 bottom-0 z-40 border-t border-black/5 bg-mist/95 px-4 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] backdrop-blur-md lg:hidden">
                <div className="mx-auto flex max-w-6xl items-center gap-3">
                    <div className="min-w-0 flex-1">
                        <p className="truncate text-sm font-semibold text-ink">{formula.name}</p>
                        <p className="text-sm font-bold text-ink">{formatMoney(formula.price)}</p>
                    </div>
                    <button
                        type="button"
                        onClick={addToCart}
                        disabled={adding || !formula.product_id}
                        className="min-h-12 shrink-0 cursor-pointer rounded-full bg-ink px-5 py-3 text-sm font-bold text-white transition hover:bg-leaf-800 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {adding ? 'Adding…' : 'Add to Cart'}
                    </button>
                </div>
            </div>
        </ShopLayout>
    );
}
