import { Link, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import CartDrawer from '../Components/CartDrawer';

export default function ShopLayout({ children, overlayHeader = false }) {
    const { app, cart, flash } = usePage().props;
    const cartCount = cart?.count ?? 0;
    const [cartOpen, setCartOpen] = useState(false);

    const openCart = useCallback(() => setCartOpen(true), []);
    const closeCart = useCallback(() => setCartOpen(false), []);

    useEffect(() => {
        if (flash?.open_cart) {
            setCartOpen(true);
        }
    }, [flash?.open_cart, cartCount]);

    return (
        <div className="relative min-h-screen overflow-x-hidden bg-mist">
            <header
                className={
                    overlayHeader
                        ? 'absolute inset-x-0 top-0 z-30 bg-transparent'
                        : 'relative z-30 border-b border-black/5 bg-mist/95 backdrop-blur-md'
                }
            >
                <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
                    <Link
                        href="/"
                        className="font-display text-2xl font-extrabold tracking-tight text-ink transition hover:opacity-80"
                    >
                        {app.name}
                    </Link>

                    <nav className="hidden items-center gap-8 text-sm font-semibold text-ink sm:flex">
                        <Link href="/" className="transition hover:opacity-60">
                            Home
                        </Link>
                        <Link href="/shop" className="transition hover:opacity-60">
                            Shop all
                        </Link>
                    </nav>

                    <div className="flex items-center gap-3">
                        <button
                            type="button"
                            onClick={openCart}
                            className="relative inline-flex items-center gap-2 rounded-full border border-ink/10 bg-white/70 px-4 py-2 text-sm font-semibold text-ink transition hover:border-ink/25"
                            aria-label={`Cart, ${cartCount} items`}
                        >
                            <svg
                                aria-hidden="true"
                                viewBox="0 0 24 24"
                                className="h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2"
                            >
                                <path d="M6 6h15l-1.5 9h-12z" />
                                <path d="M6 6 5 3H2" />
                                <circle cx="9" cy="20" r="1" />
                                <circle cx="18" cy="20" r="1" />
                            </svg>
                            <span className="hidden sm:inline">Cart</span>
                            {cartCount > 0 && (
                                <span className="absolute -top-2 -right-2 flex h-5 min-w-5 items-center justify-center rounded-full bg-ink px-1 text-[11px] font-bold text-white">
                                    {cartCount}
                                </span>
                            )}
                        </button>

                        <Link
                            href="/shop"
                            className="rounded-full bg-ink px-4 py-2 text-sm font-semibold text-white transition hover:bg-leaf-800"
                        >
                            Shop now
                        </Link>
                    </div>
                </div>
            </header>

            {flash?.error && (
                <div className="relative z-20 border-b border-punch/20 bg-punch/10 px-4 py-3 text-center text-sm font-semibold text-punch">
                    {flash.error}
                </div>
            )}

            {flash?.success && !flash?.open_cart && (
                <div className="relative z-20 border-b border-leaf-200 bg-leaf-100 px-4 py-3 text-center text-sm font-semibold text-leaf-800">
                    {flash.success}
                </div>
            )}

            <main>{children}</main>

            <footer className="border-t border-black/5 bg-ink text-white">
                <div className="mx-auto grid max-w-6xl gap-8 px-4 py-14 sm:px-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div className="space-y-3">
                        <p className="font-display text-2xl font-extrabold">{app.name}</p>
                        <p className="max-w-xs text-sm text-white/70">
                            A small daily ritual made to bring more energy to your everyday.
                        </p>
                    </div>
                    <div className="space-y-2 text-sm">
                        <p className="font-semibold tracking-wide text-white/50 uppercase">Quick links</p>
                        <Link href="/shop" className="block text-white/80 hover:text-white">
                            Shop all
                        </Link>
                        <Link href="/shop?category=wellness-shots" className="block text-white/80 hover:text-white">
                            Wellness shots
                        </Link>
                        <Link href="/faqs" className="block text-white/80 hover:text-white">
                            FAQs
                        </Link>
                        <button type="button" onClick={openCart} className="block text-left text-white/80 hover:text-white">
                            Cart
                        </button>
                    </div>
                    <div className="space-y-2 text-sm">
                        <p className="font-semibold tracking-wide text-white/50 uppercase">Support</p>
                        <p className="text-white/70">Guest checkout · one-time purchases</p>
                    </div>
                </div>
            </footer>

            <CartDrawer open={cartOpen} onClose={closeCart} />
        </div>
    );
}
