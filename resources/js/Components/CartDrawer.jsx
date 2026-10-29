import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useId, useRef, useState } from 'react';
import {
    cartMutationOptions,
    cartWithQuantity,
    emptyCartSummary,
    syncCartQuantity,
    syncCartRemove,
} from '../lib/cartVisit';

function formatMoney(value) {
    return new Intl.NumberFormat('en-GH', {
        style: 'currency',
        currency: 'GHS',
    }).format(Number(value));
}

export default function CartDrawer({ open, onClose }) {
    const { cart: serverCart } = usePage().props;
    const [cart, setCart] = useState(serverCart);
    const dirtyRef = useRef(false);
    const syncEpochRef = useRef(0);
    const titleId = useId();
    const [mounted, setMounted] = useState(open);
    const [entered, setEntered] = useState(false);

    useEffect(() => {
        if (dirtyRef.current) {
            return;
        }

        setCart(serverCart);
    }, [serverCart]);

    useEffect(() => {
        if (open) {
            setMounted(true);
            let frameTwo = 0;
            const frameOne = requestAnimationFrame(() => {
                frameTwo = requestAnimationFrame(() => setEntered(true));
            });

            const previousOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';

            const onKeyDown = (event) => {
                if (event.key === 'Escape') {
                    onClose();
                }
            };

            window.addEventListener('keydown', onKeyDown);

            return () => {
                cancelAnimationFrame(frameOne);
                cancelAnimationFrame(frameTwo);
                document.body.style.overflow = previousOverflow;
                window.removeEventListener('keydown', onKeyDown);
            };
        }

        setEntered(false);
        const timeout = window.setTimeout(() => setMounted(false), 700);

        return () => window.clearTimeout(timeout);
    }, [open, onClose]);

    const items = cart?.items ?? [];
    const count = cart?.count ?? 0;
    const subtotal = cart?.subtotal ?? '0.00';
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
        setCart((current) => cartWithQuantity(current, slug, quantity));
        syncCartQuantity(router, slug, quantity, {
            ...cartMutationOptions,
            onFinish: () => markSynced(epoch),
        });
    };

    const removeItem = (slug) => {
        dirtyRef.current = true;
        const epoch = ++syncEpochRef.current;
        setCart((current) => cartWithQuantity(current, slug, 0));
        syncCartRemove(router, slug, {
            ...cartMutationOptions,
            onFinish: () => markSynced(epoch),
        });
    };

    const clearCart = () => {
        dirtyRef.current = true;
        const epoch = ++syncEpochRef.current;
        setCart((current) => emptyCartSummary(current));
        router.cancelAll({ async: true });
        router
            .optimistic((props) => ({
                cart: emptyCartSummary(props.cart),
            }))
            .delete('/cart', {
                ...cartMutationOptions,
                onFinish: () => markSynced(epoch),
            });
    };

    if (!mounted) {
        return null;
    }

    return (
        <div className="fixed inset-0 z-50" role="presentation">
            <button
                type="button"
                aria-label="Close cart"
                className={`cart-drawer-backdrop absolute inset-0 bg-ink/35 backdrop-blur-[3px] ${
                    entered ? 'opacity-100' : 'opacity-0'
                }`}
                onClick={onClose}
            />

            <aside
                role="dialog"
                aria-modal="true"
                aria-labelledby={titleId}
                className={`cart-drawer-panel absolute inset-y-0 right-0 flex w-full max-w-md flex-col bg-white shadow-[-24px_0_60px_-28px_rgba(31,61,42,0.4)] ${
                    entered ? 'is-open' : ''
                }`}
            >
                <div className="cart-drawer-content flex min-h-0 flex-1 flex-col">
                    <div className="flex items-center justify-between border-b border-black/5 px-5 py-4 sm:px-6">
                        <div className="flex items-center gap-2.5">
                            <svg
                                aria-hidden="true"
                                viewBox="0 0 24 24"
                                className="h-5 w-5 text-ink"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2"
                            >
                                <path d="M6 6h15l-1.5 9h-12z" />
                                <path d="M6 6 5 3H2" />
                                <circle cx="9" cy="20" r="1" />
                                <circle cx="18" cy="20" r="1" />
                            </svg>
                            <h2 id={titleId} className="text-sm font-bold tracking-[0.12em] text-ink uppercase">
                                Your cart ({count})
                            </h2>
                        </div>
                        <button
                            type="button"
                            onClick={onClose}
                            className="flex h-11 w-11 cursor-pointer items-center justify-center rounded-full text-ink/50 transition hover:bg-mist hover:text-ink"
                            aria-label="Close cart"
                        >
                            <svg aria-hidden="true" viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2">
                                <path d="M6 6l12 12M18 6 6 18" />
                            </svg>
                        </button>
                    </div>

                    <div className="flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                        {items.length === 0 ? (
                            <div className="flex h-full min-h-48 flex-col items-center justify-center text-center">
                                <p className="font-display text-2xl font-extrabold text-ink">Your cart is empty</p>
                                <p className="mt-2 max-w-xs text-sm text-ink/60">
                                    Add a formula and it will show up here.
                                </p>
                                <Link
                                    href="/shop"
                                    onClick={onClose}
                                    className="mt-6 inline-flex cursor-pointer rounded-full bg-ink px-5 py-2.5 text-sm font-bold text-white transition hover:bg-leaf-800"
                                >
                                    Browse shop
                                </Link>
                            </div>
                        ) : (
                            <ul className="space-y-5">
                                {items.map((item) => (
                                    <li key={item.product.id} className="flex gap-4">
                                        <Link
                                            href={`/shop/${item.product.slug}`}
                                            onClick={onClose}
                                            className="h-20 w-20 shrink-0 overflow-hidden rounded-xl bg-[#f3f1ec]"
                                        >
                                            {item.product.image ? (
                                                <img
                                                    src={item.product.image}
                                                    alt={item.product.name}
                                                    className="h-full w-full object-cover"
                                                />
                                            ) : (
                                                <div className="flex h-full items-end bg-[linear-gradient(160deg,#c7e0bd_0%,#fff6d8_100%)] p-2">
                                                    <span className="font-display text-[10px] font-bold text-leaf-800/80">
                                                        {item.product.name}
                                                    </span>
                                                </div>
                                            )}
                                        </Link>

                                        <div className="min-w-0 flex-1">
                                            <div className="flex items-start justify-between gap-3">
                                                <div className="min-w-0">
                                                    <Link
                                                        href={`/shop/${item.product.slug}`}
                                                        onClick={onClose}
                                                        className="font-display text-lg leading-tight font-bold text-ink hover:underline"
                                                    >
                                                        {item.product.name}
                                                    </Link>
                                                    {item.product.short_description && (
                                                        <p className="mt-1 line-clamp-2 text-xs text-ink/50">
                                                            {item.product.short_description}
                                                        </p>
                                                    )}
                                                </div>
                                                <button
                                                    type="button"
                                                    onClick={() => removeItem(item.product.slug)}
                                                    className="mt-0.5 flex h-11 w-11 shrink-0 cursor-pointer items-center justify-center rounded-full text-ink/35 transition hover:bg-mist hover:text-ink"
                                                    aria-label={`Remove ${item.product.name}`}
                                                >
                                                    <svg
                                                        aria-hidden="true"
                                                        viewBox="0 0 24 24"
                                                        className="h-4 w-4"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        strokeWidth="2"
                                                    >
                                                        <path d="M4 7h16" />
                                                        <path d="M10 11v6M14 11v6" />
                                                        <path d="M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12" />
                                                        <path d="M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <p className="mt-2 text-sm font-bold text-ink">
                                                {formatMoney(item.product.price)}
                                            </p>

                                            <div className="mt-3 flex items-center gap-2">
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        updateQuantity(item.product.slug, Math.max(0, item.quantity - 1))
                                                    }
                                                    className="flex h-11 w-11 cursor-pointer items-center justify-center rounded-full border border-black/10 text-base font-bold text-ink transition hover:bg-mist"
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
                                                        updateQuantity(item.product.slug, Math.min(48, item.quantity + 1))
                                                    }
                                                    className="flex h-11 w-11 cursor-pointer items-center justify-center rounded-full border border-black/10 text-base font-bold text-ink transition hover:bg-mist"
                                                    aria-label="Increase quantity"
                                                >
                                                    +
                                                </button>
                                            </div>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>

                    {items.length > 0 && (
                        <div className="border-t border-black/5 bg-white px-5 py-5 pb-[max(1.25rem,env(safe-area-inset-bottom))] sm:px-6">
                            <div className="flex items-center justify-between">
                                <p className="text-xs font-bold tracking-[0.14em] text-ink/45 uppercase">Subtotal</p>
                                <p className="font-display text-xl font-extrabold text-ink">{formatMoney(subtotal)}</p>
                            </div>
                            <p className="mt-2 text-xs text-ink/45">Shipping shared at dispatch · preorder, made fresh.</p>
                            {!meetsMinimumOrder && (
                                <p className="mt-3 rounded-xl bg-[#f7f4ef] px-3 py-2.5 text-xs leading-relaxed font-semibold text-forest">
                                    Minimum order is {minimumOrderQuantity} shots. Add {remainingForMinimum} more to
                                    checkout.
                                </p>
                            )}

                            {meetsMinimumOrder ? (
                                <Link
                                    href="/checkout"
                                    onClick={onClose}
                                    className="mt-5 flex w-full cursor-pointer items-center justify-center rounded-xl bg-ink px-5 py-3.5 text-sm font-bold tracking-wide text-white uppercase transition hover:bg-leaf-800"
                                >
                                    Proceed to checkout
                                </Link>
                            ) : (
                                <button
                                    type="button"
                                    disabled
                                    className="mt-5 flex w-full cursor-not-allowed items-center justify-center rounded-xl bg-ink/35 px-5 py-3.5 text-sm font-bold tracking-wide text-white uppercase"
                                >
                                    Proceed to checkout
                                </button>
                            )}
                            <button
                                type="button"
                                onClick={clearCart}
                                className="mt-3 w-full cursor-pointer rounded-xl border border-ink/15 bg-white px-5 py-3.5 text-sm font-bold tracking-wide text-ink uppercase transition hover:border-ink/35"
                            >
                                Clear cart
                            </button>
                        </div>
                    )}
                </div>
            </aside>
        </div>
    );
}
