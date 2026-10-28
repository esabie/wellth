import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import PreorderNotice from '../../Components/PreorderNotice';
import ProductCard from '../../Components/ProductCard';
import ShopLayout from '../../Layouts/ShopLayout';
import { cartMutationOptions } from '../../lib/cartVisit';

function formatMoney(value) {
    return new Intl.NumberFormat('en-GH', {
        style: 'currency',
        currency: 'GHS',
    }).format(Number(value));
}

export default function Show({ product, relatedProducts }) {
    const [quantity, setQuantity] = useState(1);
    const [adding, setAdding] = useState(false);
    const { flash } = usePage().props;

    const addToCart = () => {
        setAdding(true);
        router.post('/cart', { product_id: product.id, quantity }, {
            ...cartMutationOptions,
            onFinish: () => setAdding(false),
        });
    };

    return (
        <ShopLayout>
            <Head title={product.name} />

            <section className="mx-auto grid max-w-6xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-2 lg:gap-16">
                <div className="aspect-[4/5] overflow-hidden rounded-[2rem] bg-leaf-100">
                    {product.image ? (
                        <img
                            src={product.image}
                            alt={product.name}
                            className="h-full w-full object-cover"
                        />
                    ) : (
                        <div className="flex h-full w-full items-end bg-[linear-gradient(160deg,#9ec894_0%,#fff6d8_55%,#e4f0df_100%)] p-8">
                            <span className="font-display text-4xl font-semibold text-leaf-900/80">
                                {product.name}
                            </span>
                        </div>
                    )}
                </div>

                <div className="flex flex-col justify-center space-y-6">
                    {product.category && (
                        <Link
                            href={`/shop?category=${product.category.slug}`}
                            className="text-xs font-semibold uppercase tracking-wide text-leaf-600 hover:text-leaf-500"
                        >
                            {product.category.name}
                        </Link>
                    )}

                    <h1 className="font-display text-4xl font-semibold text-ink sm:text-5xl">{product.name}</h1>

                    {product.short_description && (
                        <p className="text-lg text-leaf-800/80">{product.short_description}</p>
                    )}

                    <div className="flex items-baseline gap-3">
                        <span className="text-3xl font-semibold text-ink">{formatMoney(product.price)}</span>
                        {product.compare_at_price && (
                            <span className="text-lg text-leaf-700/50 line-through">
                                {formatMoney(product.compare_at_price)}
                            </span>
                        )}
                    </div>

                    <PreorderNotice />

                    <div className="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                        <div className="flex items-center gap-2">
                            <button
                                type="button"
                                onClick={() => setQuantity((value) => Math.max(1, value - 1))}
                                className="flex h-11 w-11 cursor-pointer items-center justify-center rounded-full border border-black/10 text-lg font-bold text-ink transition hover:bg-white"
                                aria-label="Decrease quantity"
                            >
                                −
                            </button>
                            <span className="w-8 text-center text-sm font-bold text-ink">{quantity}</span>
                            <button
                                type="button"
                                onClick={() => setQuantity((value) => Math.min(48, value + 1))}
                                className="flex h-11 w-11 cursor-pointer items-center justify-center rounded-full border border-black/10 text-lg font-bold text-ink transition hover:bg-white"
                                aria-label="Increase quantity"
                            >
                                +
                            </button>
                        </div>

                        <button
                            type="button"
                            onClick={addToCart}
                            disabled={adding}
                            className="min-h-12 w-full cursor-pointer rounded-full bg-ink px-6 py-3 text-sm font-bold text-white transition hover:bg-leaf-800 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
                        >
                            {adding ? 'Adding…' : 'Add to cart'}
                        </button>
                    </div>

                    {flash?.success && (
                        <p className="text-sm font-semibold text-leaf-700">{flash.success}</p>
                    )}

                    {product.description && (
                        <div className="space-y-2 border-t border-leaf-200 pt-6">
                            <h2 className="text-sm font-semibold uppercase tracking-wide text-leaf-700">Details</h2>
                            <p className="whitespace-pre-line text-leaf-800/80">{product.description}</p>
                        </div>
                    )}
                </div>
            </section>

            {relatedProducts.length > 0 && (
                <section className="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
                    <h2 className="mb-8 font-display text-3xl font-semibold text-ink">You may also like</h2>
                    <div className="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                        {relatedProducts.map((related) => (
                            <ProductCard key={related.id} product={related} />
                        ))}
                    </div>
                </section>
            )}
        </ShopLayout>
    );
}
