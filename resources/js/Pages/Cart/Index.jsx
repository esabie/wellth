import { Head, Link, router, usePage } from '@inertiajs/react';
import ShopLayout from '../../Layouts/ShopLayout';

function formatMoney(value) {
    return new Intl.NumberFormat('en-GH', {
        style: 'currency',
        currency: 'GHS',
    }).format(Number(value));
}

export default function Index({ items, subtotal }) {
    const { cart } = usePage().props;
    const minimumOrderQuantity = cart?.minimum_order_quantity ?? 10;
    const meetsMinimumOrder = cart?.meets_minimum_order ?? false;
    const remainingForMinimum = cart?.remaining_for_minimum_order ?? minimumOrderQuantity;

    const updateQuantity = (slug, quantity) => {
        router.patch(`/cart/${slug}`, { quantity }, { preserveScroll: true });
    };

    const removeItem = (slug) => {
        router.delete(`/cart/${slug}`, { preserveScroll: true });
    };

    return (
        <ShopLayout>
            <Head title="Your cart" />

            <section className="mx-auto max-w-4xl px-4 py-12 sm:px-6">
                <h1 className="font-display text-4xl font-extrabold tracking-tight text-ink">Your cart</h1>
                <p className="mt-2 text-ink/65">Guest checkout · preorder · minimum {minimumOrderQuantity} shots</p>

                {items.length === 0 ? (
                    <div className="mt-12 rounded-[1.5rem] bg-white p-10 text-center ring-1 ring-black/5">
                        <p className="text-lg font-semibold text-ink">Nothing in here yet</p>
                        <p className="mt-2 text-ink/65">Pick your favourites and make wellness feel simple.</p>
                        <Link
                            href="/shop"
                            className="mt-6 inline-flex rounded-full bg-ink px-6 py-3 text-sm font-bold text-white transition hover:bg-leaf-800"
                        >
                            Shop all shots
                        </Link>
                    </div>
                ) : (
                    <div className="mt-10 space-y-8">
                        <ul className="divide-y divide-black/5 overflow-hidden rounded-[1.5rem] bg-white ring-1 ring-black/5">
                            {items.map((item) => (
                                <li key={item.product.id} className="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                                    <div className="flex items-center gap-4">
                                        <div className="h-20 w-16 overflow-hidden rounded-xl bg-leaf-100">
                                            {item.product.image ? (
                                                <img
                                                    src={item.product.image}
                                                    alt={item.product.name}
                                                    className="h-full w-full object-cover"
                                                />
                                            ) : (
                                                <div className="flex h-full items-end bg-[linear-gradient(160deg,#c7e0bd_0%,#fff6d8_100%)] p-2">
                                                    <span className="font-display text-xs font-bold text-leaf-800/80">
                                                        {item.product.name}
                                                    </span>
                                                </div>
                                            )}
                                        </div>
                                        <div>
                                            <Link
                                                href={`/shop/${item.product.slug}`}
                                                className="font-display text-lg font-bold text-ink hover:underline"
                                            >
                                                {item.product.name}
                                            </Link>
                                            <p className="text-sm text-ink/60">{formatMoney(item.product.price)} each</p>
                                        </div>
                                    </div>

                                    <div className="flex items-center justify-between gap-4 sm:justify-end">
                                        <div className="flex items-center gap-2">
                                            <button
                                                type="button"
                                                onClick={() => updateQuantity(item.product.slug, item.quantity - 1)}
                                                className="flex h-9 w-9 items-center justify-center rounded-full border border-black/10 text-lg font-bold text-ink transition hover:bg-mist"
                                                aria-label="Decrease quantity"
                                            >
                                                −
                                            </button>
                                            <span className="w-8 text-center text-sm font-bold text-ink">{item.quantity}</span>
                                            <button
                                                type="button"
                                                onClick={() => updateQuantity(item.product.slug, Math.min(48, item.quantity + 1))}
                                                className="flex h-9 w-9 items-center justify-center rounded-full border border-black/10 text-lg font-bold text-ink transition hover:bg-mist"
                                                aria-label="Increase quantity"
                                            >
                                                +
                                            </button>
                                        </div>
                                        <p className="w-20 text-right text-sm font-bold text-ink">
                                            {formatMoney(item.line_total)}
                                        </p>
                                        <button
                                            type="button"
                                            onClick={() => removeItem(item.product.slug)}
                                            className="text-sm font-semibold text-ink/50 transition hover:text-ink"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                </li>
                            ))}
                        </ul>

                        <div className="flex flex-col gap-4 rounded-[1.5rem] bg-white p-6 ring-1 ring-black/5 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p className="text-sm font-semibold uppercase tracking-wide text-ink/45">Subtotal</p>
                                <p className="font-display text-3xl font-extrabold text-ink">{formatMoney(subtotal)}</p>
                                {!meetsMinimumOrder && (
                                    <p className="mt-2 max-w-sm text-sm font-semibold text-forest">
                                        Minimum order is {minimumOrderQuantity} shots. Add {remainingForMinimum} more to
                                        checkout.
                                    </p>
                                )}
                            </div>
                            {meetsMinimumOrder ? (
                                <Link
                                    href="/checkout"
                                    className="inline-flex cursor-pointer rounded-full bg-ink px-6 py-3 text-sm font-bold text-white transition hover:bg-leaf-800"
                                >
                                    Proceed to checkout
                                </Link>
                            ) : (
                                <button
                                    type="button"
                                    disabled
                                    className="inline-flex cursor-not-allowed rounded-full bg-ink/35 px-6 py-3 text-sm font-bold text-white"
                                >
                                    Proceed to checkout
                                </button>
                            )}
                        </div>
                    </div>
                )}
            </section>
        </ShopLayout>
    );
}
