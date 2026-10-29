import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { cartMutationOptions } from '../lib/cartVisit';

function formatMoney(value) {
    return new Intl.NumberFormat('en-GH', {
        style: 'currency',
        currency: 'GHS',
    }).format(Number(value));
}

export default function ProductCard({ product }) {
    const [adding, setAdding] = useState(false);

    const addToCart = (event) => {
        event.preventDefault();
        event.stopPropagation();

        if (!product.id || adding) {
            return;
        }

        setAdding(true);
        router.post('/cart', { product_id: product.id, quantity: 1 }, {
            ...cartMutationOptions,
            onFinish: () => setAdding(false),
        });
    };

    return (
        <article className="group flex h-full flex-col overflow-hidden rounded-xl bg-white shadow-[0_10px_40px_-12px_rgba(31,61,42,0.22)] ring-1 ring-black/[0.04] transition duration-300 hover:-translate-y-1 hover:shadow-[0_22px_50px_-14px_rgba(31,61,42,0.3)] sm:rounded-2xl">
            <Link href={`/shop/${product.slug}`} className="flex min-h-0 flex-1 flex-col">
                <div className="relative aspect-square overflow-hidden bg-[#ebe6dc]">
                    {product.image ? (
                        <img
                            src={product.image}
                            alt={product.name}
                            className="h-full w-full object-cover object-center transition duration-700 ease-out group-hover:scale-105"
                            loading="lazy"
                        />
                    ) : (
                        <div className="flex h-full items-center justify-center px-2 text-center font-display text-sm text-forest/30 sm:text-lg">
                            {product.name}
                        </div>
                    )}
                    <div className="pointer-events-none absolute inset-x-0 bottom-0 h-12 bg-gradient-to-t from-black/10 to-transparent sm:h-16" />
                </div>

                <div className="flex flex-1 flex-col px-3 pt-3 sm:px-5 sm:pt-4">
                    <div className="flex flex-col gap-0.5 sm:flex-row sm:items-start sm:justify-between sm:gap-3">
                        <h3 className="line-clamp-2 font-display text-[0.95rem] leading-tight font-bold tracking-tight text-forest sm:text-lg">
                            {product.name}
                        </h3>
                        {product.bottle_size && (
                            <span className="shrink-0 text-[10px] font-semibold tracking-[0.12em] text-forest/40 uppercase sm:text-[11px]">
                                {product.bottle_size}
                            </span>
                        )}
                    </div>
                    <p className="mt-1.5 text-sm font-semibold tracking-tight text-ink sm:mt-2 sm:text-base">
                        {formatMoney(product.price)}
                    </p>
                    {product.short_description && (
                        <p className="mt-1.5 hidden line-clamp-2 text-sm leading-relaxed text-forest/60 sm:mt-2 sm:block">
                            {product.short_description}
                        </p>
                    )}
                </div>
            </Link>

            <div className="mt-auto px-3 pt-3 pb-3 sm:px-5 sm:pt-4 sm:pb-5">
                <button
                    type="button"
                    onClick={addToCart}
                    disabled={adding}
                    className="flex min-h-10 w-full cursor-pointer items-center justify-center gap-1.5 rounded-full bg-ink px-2.5 py-2.5 text-xs font-bold text-white shadow-[0_8px_20px_-10px_rgba(31,61,42,0.55)] transition hover:bg-leaf-800 disabled:cursor-not-allowed disabled:opacity-60 sm:min-h-11 sm:gap-2 sm:px-4 sm:py-3 sm:text-sm"
                >
                    <svg
                        aria-hidden="true"
                        viewBox="0 0 24 24"
                        className="h-3.5 w-3.5 sm:h-4 sm:w-4"
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
            </div>
        </article>
    );
}
