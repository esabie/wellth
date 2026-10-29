import { Link, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useId, useState } from 'react';
import CartDrawer from '../Components/CartDrawer';

const NAV_LINKS = [
    { href: '/', label: 'Home' },
    { href: '/shop', label: 'Shop all' },
];

export default function ShopLayout({ children, overlayHeader = false }) {
    const { app, cart, flash } = usePage().props;
    const cartCount = cart?.count ?? 0;
    const [cartOpen, setCartOpen] = useState(false);
    const [menuOpen, setMenuOpen] = useState(false);
    const menuId = useId();

    const openCart = useCallback(() => {
        setMenuOpen(false);
        setCartOpen(true);
    }, []);
    const closeCart = useCallback(() => setCartOpen(false), []);
    const closeMenu = useCallback(() => setMenuOpen(false), []);
    const toggleMenu = useCallback(() => setMenuOpen((open) => !open), []);

    useEffect(() => {
        if (flash?.open_cart) {
            setCartOpen(true);
        }
    }, [flash?.open_cart]);

    useEffect(() => {
        if (!menuOpen) {
            return undefined;
        }

        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        const onKeyDown = (event) => {
            if (event.key === 'Escape') {
                setMenuOpen(false);
            }
        };

        window.addEventListener('keydown', onKeyDown);

        return () => {
            document.body.style.overflow = previousOverflow;
            window.removeEventListener('keydown', onKeyDown);
        };
    }, [menuOpen]);

    return (
        <div className="relative min-h-screen overflow-x-hidden bg-mist">
            <header
                className={
                    overlayHeader
                        ? 'absolute inset-x-0 top-0 z-30 bg-mist/75 backdrop-blur-md sm:bg-transparent sm:backdrop-blur-none'
                        : 'relative z-30 border-b border-black/5 bg-mist/95 backdrop-blur-md'
                }
            >
                <div className="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3 sm:gap-4 sm:px-6 sm:py-4">
                    <Link
                        href="/"
                        className="min-w-0 truncate font-display text-xl font-extrabold tracking-tight text-ink transition hover:opacity-80 sm:text-2xl"
                    >
                        {app.name}
                    </Link>

                    <nav className="hidden items-center gap-8 text-sm font-semibold text-ink sm:flex">
                        {NAV_LINKS.map((link) => (
                            <Link key={link.href} href={link.href} className="transition hover:opacity-60">
                                {link.label}
                            </Link>
                        ))}
                    </nav>

                    <div className="flex shrink-0 items-center gap-2 sm:gap-3">
                        <button
                            type="button"
                            onClick={openCart}
                            className="relative inline-flex min-h-11 min-w-11 items-center justify-center gap-2 rounded-full border border-ink/10 bg-white/80 px-3 text-sm font-semibold text-ink transition hover:border-ink/25 sm:px-4"
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
                            className="hidden min-h-11 items-center rounded-full bg-ink px-4 py-2 text-sm font-semibold text-white transition hover:bg-leaf-800 sm:inline-flex"
                        >
                            Shop now
                        </Link>

                        <button
                            type="button"
                            onClick={toggleMenu}
                            className="inline-flex min-h-11 min-w-11 items-center justify-center rounded-full border border-ink/10 bg-white/80 text-ink transition hover:border-ink/25 sm:hidden"
                            aria-expanded={menuOpen}
                            aria-controls={menuId}
                            aria-label={menuOpen ? 'Close menu' : 'Open menu'}
                        >
                            {menuOpen ? (
                                <svg aria-hidden="true" viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2">
                                    <path d="M6 6l12 12M18 6 6 18" />
                                </svg>
                            ) : (
                                <svg aria-hidden="true" viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2">
                                    <path d="M4 7h16M4 12h16M4 17h16" />
                                </svg>
                            )}
                        </button>
                    </div>
                </div>

                {menuOpen && (
                    <div
                        id={menuId}
                        className="border-t border-black/5 bg-mist/95 px-4 pb-[max(1rem,env(safe-area-inset-bottom))] pt-2 backdrop-blur-md sm:hidden"
                    >
                        <nav className="flex flex-col py-2">
                            {NAV_LINKS.map((link) => (
                                <Link
                                    key={link.href}
                                    href={link.href}
                                    onClick={closeMenu}
                                    className="flex min-h-12 items-center border-b border-black/5 text-base font-semibold text-ink last:border-b-0"
                                >
                                    {link.label}
                                </Link>
                            ))}
                            <button
                                type="button"
                                onClick={openCart}
                                className="flex min-h-12 items-center text-left text-base font-semibold text-ink"
                            >
                                Cart{cartCount > 0 ? ` (${cartCount})` : ''}
                            </button>
                        </nav>
                    </div>
                )}
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

            <footer className="border-t border-black/5 bg-ink pb-[env(safe-area-inset-bottom)] text-white">
                <div className="mx-auto grid max-w-6xl gap-8 px-4 py-14 sm:px-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div className="space-y-3">
                        <p className="font-display text-2xl font-extrabold">{app.name}</p>
                        <p className="max-w-xs text-sm text-white/70">
                            A small daily ritual made to bring more energy to your everyday.
                        </p>
                    </div>
                    <div className="space-y-2 text-sm">
                        <p className="font-semibold tracking-wide text-white/50 uppercase">Quick links</p>
                        <Link href="/shop" className="block min-h-10 py-1.5 text-white/80 hover:text-white">
                            Shop all
                        </Link>
                        <Link href="/shop?category=wellness-shots" className="block min-h-10 py-1.5 text-white/80 hover:text-white">
                            Wellness shots
                        </Link>
                        <Link href="/faqs" className="block min-h-10 py-1.5 text-white/80 hover:text-white">
                            FAQs
                        </Link>
                        <button type="button" onClick={openCart} className="block min-h-10 py-1.5 text-left text-white/80 hover:text-white">
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
