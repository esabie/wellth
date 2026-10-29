import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import ShopLayout from '../../Layouts/ShopLayout';
import {
    cartPageMutationOptions,
    cartWithQuantity,
    syncCartQuantity,
    syncCartRemove,
} from '../../lib/cartVisit';

function formatMoney(value) {
    return new Intl.NumberFormat('en-GH', {
        style: 'currency',
        currency: 'GHS',
    }).format(Number(value));
}

export default function Index({ items: serverItems, subtotal: serverSubtotal }) {
    const { cart: serverCart } = usePage().props;
    const [cart, setCart] = useState(serverCart);
    const [items, setItems] = useState(serverItems);
    const [subtotal, setSubtotal] = useState(serverSubtotal);
    const dirtyRef = useRef(false);
    const syncEpochRef = useRef(0);

    useEffect(() => {
        if (dirtyRef.current) {
            return;
        }

        setCart(serverCart);
        setItems(serverItems);
        setSubtotal(serverSubtotal);
    }, [serverCart, serverItems, serverSubtotal]);

    const minimumOrderQuantity = cart?.minimum_order_quantity ?? 10;
    const meetsMinimumOrder = cart?.meets_minimum_order ?? false;
    const remainingForMinimum = cart?.remaining_for_minimum_order ?? minimumOrderQuantity;

    const markSynced = (epoch) => {
        if (epoch === syncEpochRef.current) {
            dirtyRef.current = false;
        }
    };

    const updateQuantity = (slug, quantity) => {
        dirtyRef.current = true;
        const epoch = ++syncEpochRef.current;
        const nextCart = cartWithQuantity(cart, slug, quantity);
        setCart(nextCart);
        setItems(nextCart.items);
        setSubtotal(nextCart.subtotal);
        syncCartQuantity(router, slug, quantity, {
            ...cartPageMutationOptions,
            onFinish: () => markSynced(epoch),
        });
    };

    const removeItem = (slug) => {
        dirtyRef.current = true;
        const epoch = ++syncEpochRef.current;
        const nextCart = cartWithQuantity(cart, slug, 0);
        setCart(nextCart);
        setItems(nextCart.items);
        setSubtotal(nextCart.subtotal);
        syncCartRemove(router, slug, {
            ...cartPageMutationOptions,
            onFinish: () => markSynced(epoch),
        });
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
                                <li
                                    key={item.product.id}
                                    className="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between"
                                >
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
                                                onClick={() =>
                                                    updateQuantity(item.product.slug, item.quantity - 1)
                                                }
                                                className="flex h-11 w-11 items-center justify-center rounded-full border border-black/10 text-lg font-bold text-ink transition hover:bg-mist"
                                                aria-label="Decrease quantity"
                                            >
                                                −
                                            </button>
                                            <span className="w-8 text-center text-sm font-bold text-ink">
                                                {item.quantity}
                                            </span>
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    updateQuantity(
                                                        item.product.slug,
                                                        Math.min(48, item.quantity + 1),
                                                    )
                                                }
                                                className="flex h-11 w-11 items-center justify-center rounded-full border border-black/10 text-lg font-bold text-ink transition hover:bg-mist"
                                                aria-label="Increase quantity"
                                            >
                                                +
                                            </button>
                                        </div>
                                        <p className="min-w-16 text-right text-sm font-bold text-ink">
                                            {formatMoney(item.line_total)}
                                        </p>
                                        <button
                                            type="button"
                                            onClick={() => removeItem(item.product.slug)}
                                            className="min-h-11 px-2 text-sm font-semibold text-ink/50 transition hover:text-ink"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                </li>
                            ))}
                        </ul>

                        <div className="flex flex-col gap-4 rounded-[1.5rem] bg-white p-6 ring-1 ring-black/5 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p className="text-sm font-semibold tracking-wide text-ink/45 uppercase">Subtotal</p>
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
                                    className="inline-flex min-h-12 w-full cursor-pointer items-center justify-center rounded-full bg-ink px-6 py-3 text-sm font-bold text-white transition hover:bg-leaf-800 sm:w-auto"
                                >
                                    Proceed to checkout
                                </Link>
                            ) : (
                                <button
                                    type="button"
                                    disabled
                                    className="inline-flex min-h-12 w-full cursor-not-allowed items-center justify-center rounded-full bg-ink/35 px-6 py-3 text-sm font-bold text-white sm:w-auto"
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
