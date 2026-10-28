import { Link, router } from '@inertiajs/react';
import { useState } from 'react';

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
        router.post(
            '/cart',
            { product_id: product.id, quantity: 1 },
            {
                preserveScroll: true,
                onFinish: () => setAdding(false),
            },
        );
    };

    return (
        <article className="group flex h-full flex-col overflow-hidden rounded-2xl bg-white shadow-[0_10px_40px_-12px_rgba(31,61,42,0.22)] ring-1 ring-black/[0.04] transition duration-300 hover:-translate-y-1 hover:shadow-[0_22px_50px_-14px_rgba(31,61,42,0.3)]">
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
                        <div className="flex h-full items-center justify-center font-display text-lg text-forest/30">
                            {product.name}
                        </div>
                    )}
                    <div className="pointer-events-none absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-black/10 to-transparent" />
                </div>

                <div className="flex flex-1 flex-col px-5 pt-4">
                    <div className="flex items-start justify-between gap-3">
                        <h3 className="font-display text-lg leading-tight font-bold tracking-tight text-forest">
                            {product.name}
                        </h3>
                        {product.bottle_size && (
                            <span className="mt-1 shrink-0 text-[11px] font-semibold tracking-[0.12em] text-forest/40 uppercase">
                                {product.bottle_size}
                            </span>
                        )}
                    </div>
                    <p className="mt-2 text-base font-semibold tracking-tight text-ink">
                        {formatMoney(product.price)}
                    </p>
                    {product.short_description && (
                        <p className="mt-2 line-clamp-2 text-sm leading-relaxed text-forest/60">
                            {product.short_description}
                        </p>
                    )}
                </div>
            </Link>

            <div className="mt-auto px-5 pt-4 pb-5">
                <button
                    type="button"
                    onClick={addToCart}
                    disabled={adding}
                    className="flex w-full cursor-pointer items-center justify-center gap-2 rounded-full bg-ink px-4 py-3 text-sm font-bold text-white shadow-[0_8px_20px_-10px_rgba(31,61,42,0.55)] transition hover:bg-leaf-800 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <svg
                        aria-hidden="true"
                        viewBox="0 0 24 24"
                        className="h-4 w-4"
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
