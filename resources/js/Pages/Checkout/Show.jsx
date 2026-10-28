import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import ShopLayout from '../../Layouts/ShopLayout';

function formatMoney(value) {
    return new Intl.NumberFormat('en-GH', {
        style: 'currency',
        currency: 'GHS',
    }).format(Number(value));
}

function FieldError({ message }) {
    if (!message) {
        return null;
    }

    return <p className="mt-1.5 text-xs font-semibold text-punch">{message}</p>;
}

const fieldClassName =
    'mt-1.5 w-full rounded-xl border border-black/10 bg-white px-4 py-3 text-base text-ink outline-none transition placeholder:text-ink/35 focus:border-forest/40 sm:text-sm';

export default function Show({
    items,
    subtotal,
    shipping_amount,
    discount_amount,
    loyalty_discount_amount = '0.00',
    loyalty_label = null,
    loyalty_eligible = false,
    promo_code,
    promo_label,
    promo_discount_amount = '0.00',
    points_enabled = false,
    points_redeeming = false,
    points_balance = 0,
    points_balance_value = '0.00',
    points_redeemed = 0,
    points_discount_amount = '0.00',
    points_per_paid_order = 10,
    total,
    locations,
}) {
    const { errors, app } = usePage().props;
    const [region, setRegion] = useState('Greater Accra');
    const [city, setCity] = useState('Accra');
    const [promoInput, setPromoInput] = useState(promo_code ?? '');
    const [applyingPromo, setApplyingPromo] = useState(false);
    const [checkingLoyalty, setCheckingLoyalty] = useState(false);
    const [togglingPoints, setTogglingPoints] = useState(false);

    useEffect(() => {
        setPromoInput(promo_code ?? '');
    }, [promo_code]);

    const cities = useMemo(() => locations?.[region] ?? [], [locations, region]);
    const hasLoyaltyDiscount = Number(loyalty_discount_amount) > 0;
    const hasPromoDiscount = Number(promo_discount_amount) > 0;
    const hasPointsDiscount = Number(points_discount_amount) > 0;
    const hasDiscount = Number(discount_amount) > 0;
    const canUsePoints = points_enabled && Number(points_balance) > 0;

    const selectRegion = (nextRegion) => {
        setRegion(nextRegion);
        const nextCities = locations?.[nextRegion] ?? [];
        setCity(nextCities[0] ?? '');
    };

    const applyPromo = (event) => {
        event.preventDefault();
        setApplyingPromo(true);
        router.post(
            '/checkout/promo',
            { code: promoInput },
            {
                preserveScroll: true,
                onFinish: () => setApplyingPromo(false),
            },
        );
    };

    const removePromo = () => {
        router.delete('/checkout/promo', { preserveScroll: true });
        setPromoInput('');
    };

    const applyPoints = () => {
        setTogglingPoints(true);
        router.post(
            '/checkout/points',
            {},
            {
                preserveScroll: true,
                onFinish: () => setTogglingPoints(false),
            },
        );
    };

    const removePoints = () => {
        setTogglingPoints(true);
        router.delete('/checkout/points', {
            preserveScroll: true,
            onFinish: () => setTogglingPoints(false),
        });
    };

    const previewLoyalty = (phone) => {
        const trimmed = phone.trim();

        if (trimmed.length < 9) {
            return;
        }

        setCheckingLoyalty(true);
        router.post(
            '/checkout/loyalty-preview',
            { customer_phone: trimmed },
            {
                preserveScroll: true,
                onFinish: () => setCheckingLoyalty(false),
            },
        );
    };

    return (
        <ShopLayout>
            <Head title="Checkout" />

            <section className="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <p className="text-sm text-ink/45">
                        <Link href="/" className="transition hover:text-ink">
                            Home
                        </Link>
                        <span className="mx-1.5">/</span>
                        <Link href="/shop" className="transition hover:text-ink">
                            Shop
                        </Link>
                        <span className="mx-1.5">/</span>
                        <span className="font-semibold text-ink">Checkout</span>
                    </p>
                    <Link
                        href="/shop"
                        className="text-sm font-semibold text-forest transition hover:text-leaf-700"
                    >
                        Continue shopping
                    </Link>
                </div>

                {errors.checkout && (
                    <p className="mt-6 rounded-xl bg-punch/10 px-4 py-3 text-sm font-semibold text-punch">
                        {errors.checkout}
                    </p>
                )}

                <Form
                    action="/checkout"
                    method="post"
                    className="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1.15fr)_minmax(20rem,0.85fr)] lg:items-start"
                >
                    {({ processing, errors: formErrors }) => (
                        <>
                            <div className="space-y-5">
                                <div className="rounded-2xl border border-black/8 bg-white p-5 sm:p-6">
                                    <p className="text-[11px] font-bold tracking-[0.16em] text-ink/45 uppercase">
                                        You are checking out as a guest
                                    </p>
                                    <p className="mt-2 text-sm leading-relaxed text-ink/70">
                                        No account needed. We&apos;ll email and text your order confirmation and prepare your
                                        shots fresh. Please allow up to one week for delivery.
                                    </p>
                                </div>

                                <div className="rounded-2xl border border-black/8 bg-white p-5 sm:p-6">
                                    <h2 className="font-display text-xl font-extrabold tracking-tight text-ink">
                                        Contact
                                    </h2>
                                    <div className="mt-5 grid gap-4 sm:grid-cols-2">
                                        <label className="block sm:col-span-2">
                                            <span className="text-xs font-bold tracking-wide text-ink/50 uppercase">
                                                Full name
                                            </span>
                                            <input
                                                type="text"
                                                name="customer_name"
                                                required
                                                autoComplete="name"
                                                className={fieldClassName}
                                            />
                                            <FieldError message={formErrors.customer_name} />
                                        </label>
                                        <label className="block">
                                            <span className="text-xs font-bold tracking-wide text-ink/50 uppercase">
                                                Email
                                            </span>
                                            <input
                                                type="email"
                                                name="customer_email"
                                                required
                                                autoComplete="email"
                                                className={fieldClassName}
                                            />
                                            <FieldError message={formErrors.customer_email} />
                                        </label>
                                        <label className="block">
                                            <span className="text-xs font-bold tracking-wide text-ink/50 uppercase">
                                                Phone
                                            </span>
                                            <input
                                                type="tel"
                                                name="customer_phone"
                                                required
                                                autoComplete="tel"
                                                placeholder="e.g. 024 123 4567"
                                                className={fieldClassName}
                                                onBlur={(event) => previewLoyalty(event.target.value)}
                                            />
                                            <FieldError message={formErrors.customer_phone} />
                                            {checkingLoyalty && (
                                                <p className="mt-1.5 text-xs font-medium text-ink/45">Checking offer…</p>
                                            )}
                                            {loyalty_eligible && hasLoyaltyDiscount && !points_redeeming && (
                                                <p className="mt-1.5 text-xs font-semibold text-forest">
                                                    {loyalty_label
                                                        ? `${loyalty_label} unlocked for this order.`
                                                        : 'Second purchase perk unlocked for this order.'}
                                                </p>
                                            )}
                                            {points_enabled && Number(points_balance) > 0 && (
                                                <p className="mt-1.5 text-xs font-semibold text-ink/70">
                                                    You have {points_balance} points ≈ {formatMoney(points_balance_value)}.
                                                    {points_per_paid_order > 0
                                                        ? ` Earn ${points_per_paid_order} points when this order is paid.`
                                                        : ''}
                                                </p>
                                            )}
                                        </label>
                                    </div>
                                </div>

                                <div className="rounded-2xl border border-black/8 bg-white p-5 sm:p-6">
                                    <h2 className="font-display text-xl font-extrabold tracking-tight text-ink">
                                        Delivery address
                                    </h2>
                                    <div className="mt-5 grid gap-4">
                                        <label className="block">
                                            <span className="text-xs font-bold tracking-wide text-ink/50 uppercase">
                                                Street address
                                            </span>
                                            <input
                                                type="text"
                                                name="shipping_line1"
                                                required
                                                autoComplete="address-line1"
                                                className={fieldClassName}
                                            />
                                            <FieldError message={formErrors.shipping_line1} />
                                        </label>
                                        <label className="block">
                                            <span className="text-xs font-bold tracking-wide text-ink/50 uppercase">
                                                Apartment, suite, etc. (optional)
                                            </span>
                                            <input
                                                type="text"
                                                name="shipping_line2"
                                                autoComplete="address-line2"
                                                className={fieldClassName}
                                            />
                                            <FieldError message={formErrors.shipping_line2} />
                                        </label>
                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <label className="block">
                                                <span className="text-xs font-bold tracking-wide text-ink/50 uppercase">
                                                    Region
                                                </span>
                                                <select
                                                    name="shipping_region"
                                                    required
                                                    value={region}
                                                    onChange={(event) => selectRegion(event.target.value)}
                                                    className={fieldClassName}
                                                >
                                                    {Object.keys(locations ?? {}).map((name) => (
                                                        <option key={name} value={name}>
                                                            {name}
                                                        </option>
                                                    ))}
                                                </select>
                                                <FieldError message={formErrors.shipping_region} />
                                            </label>
                                            <label className="block">
                                                <span className="text-xs font-bold tracking-wide text-ink/50 uppercase">
                                                    City
                                                </span>
                                                <select
                                                    name="shipping_city"
                                                    required
                                                    value={city}
                                                    onChange={(event) => setCity(event.target.value)}
                                                    disabled={cities.length === 0}
                                                    className={`${fieldClassName} disabled:cursor-not-allowed disabled:bg-mist/70`}
                                                >
                                                    {cities.length === 0 ? (
                                                        <option value="">Select a region first</option>
                                                    ) : (
                                                        cities.map((name) => (
                                                            <option key={name} value={name}>
                                                                {name}
                                                            </option>
                                                        ))
                                                    )}
                                                </select>
                                                <FieldError message={formErrors.shipping_city} />
                                            </label>
                                        </div>
                                        <label className="block">
                                            <span className="text-xs font-bold tracking-wide text-ink/50 uppercase">
                                                Additional notes (optional)
                                            </span>
                                            <textarea
                                                name="shipping_notes"
                                                rows={3}
                                                className={fieldClassName}
                                                placeholder="e.g. Place at the door, landmark, preferred time…"
                                            />
                                            <FieldError message={formErrors.shipping_notes} />
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <aside className="lg:sticky lg:top-6">
                                <div className="rounded-2xl border border-black/8 bg-white p-5 sm:p-6">
                                    <h2 className="font-display text-xl font-extrabold tracking-tight text-ink">
                                        Order summary
                                    </h2>

                                    <ul className="mt-5 space-y-4">
                                        {items.map((item) => (
                                            <li key={item.product.id} className="flex gap-3">
                                                <div className="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-[#f3f1ec]">
                                                    {item.product.image ? (
                                                        <img
                                                            src={item.product.image}
                                                            alt={item.product.name}
                                                            className="h-full w-full object-cover"
                                                        />
                                                    ) : (
                                                        <div className="flex h-full items-end bg-[linear-gradient(160deg,#c7e0bd_0%,#fff6d8_100%)] p-1.5">
                                                            <span className="font-display text-[9px] font-bold text-leaf-800/80">
                                                                {item.product.name}
                                                            </span>
                                                        </div>
                                                    )}
                                                </div>
                                                <div className="min-w-0 flex-1">
                                                    <div className="flex items-start justify-between gap-3">
                                                        <div className="min-w-0">
                                                            <p className="truncate text-sm font-bold tracking-wide text-ink uppercase">
                                                                {item.product.name}
                                                            </p>
                                                            <p className="mt-0.5 text-[11px] font-semibold tracking-wide text-ink/40 uppercase">
                                                                Brand: {app?.name ?? 'Wellth'}
                                                            </p>
                                                            <p className="mt-1 text-xs text-ink/50">
                                                                Qty {item.quantity}
                                                            </p>
                                                        </div>
                                                        <p className="shrink-0 text-sm font-bold text-ink">
                                                            {formatMoney(item.line_total)}
                                                        </p>
                                                    </div>
                                                </div>
                                            </li>
                                        ))}
                                    </ul>

                                    <div className="mt-6 space-y-2.5 border-t border-black/5 pt-5 text-sm">
                                        <div className="flex justify-between text-ink/65">
                                            <span>Subtotal</span>
                                            <span className="font-semibold text-ink">{formatMoney(subtotal)}</span>
                                        </div>
                                        <div className="flex justify-between text-ink/65">
                                            <span>Shipping</span>
                                            <span className="font-semibold text-ink">
                                                {Number(shipping_amount) > 0
                                                    ? formatMoney(shipping_amount)
                                                    : 'Shared at dispatch'}
                                            </span>
                                        </div>
                                        {hasLoyaltyDiscount && (
                                            <div className="flex justify-between text-forest">
                                                <span>{loyalty_label ?? 'Second purchase 5% off'}</span>
                                                <span className="font-semibold">
                                                    −{formatMoney(loyalty_discount_amount)}
                                                </span>
                                            </div>
                                        )}
                                        {hasPointsDiscount && (
                                            <div className="flex justify-between text-forest">
                                                <span>
                                                    Loyalty points
                                                    {points_redeemed > 0 ? ` (${points_redeemed} pts)` : ''}
                                                </span>
                                                <span className="font-semibold">
                                                    −{formatMoney(points_discount_amount)}
                                                </span>
                                            </div>
                                        )}
                                        {hasPromoDiscount && (
                                            <div className="flex justify-between text-forest">
                                                <span>
                                                    Promo
                                                    {promo_label ? ` (${promo_label})` : promo_code ? ` (${promo_code})` : ''}
                                                </span>
                                                <span className="font-semibold">
                                                    −{formatMoney(promo_discount_amount)}
                                                </span>
                                            </div>
                                        )}
                                        {hasDiscount &&
                                            !hasLoyaltyDiscount &&
                                            !hasPromoDiscount &&
                                            !hasPointsDiscount && (
                                            <div className="flex justify-between text-forest">
                                                <span>Discount</span>
                                                <span className="font-semibold">−{formatMoney(discount_amount)}</span>
                                            </div>
                                        )}
                                        <div className="flex justify-between border-t border-black/5 pt-3 text-base font-extrabold text-ink">
                                            <span>Total</span>
                                            <span>{formatMoney(total)}</span>
                                        </div>
                                    </div>

                                    {canUsePoints && (
                                        <div className="mt-5 border-t border-black/5 pt-5">
                                            <div className="flex items-start justify-between gap-3">
                                                <div>
                                                    <p className="text-sm font-semibold text-ink">Loyalty points</p>
                                                    <p className="mt-1 text-xs leading-relaxed text-ink/55">
                                                        {points_redeeming
                                                            ? 'Using points on this order. Promo codes and the second-purchase discount are paused.'
                                                            : `Apply up to ${formatMoney(points_balance_value)}. This replaces promo codes and the second-purchase discount.`}
                                                    </p>
                                                </div>
                                                <button
                                                    type="button"
                                                    onClick={points_redeeming ? removePoints : applyPoints}
                                                    disabled={togglingPoints}
                                                    className="min-h-11 cursor-pointer shrink-0 rounded-xl border border-black/10 px-4 py-2.5 text-sm font-bold tracking-wide text-ink uppercase transition hover:bg-mist/60 disabled:cursor-not-allowed disabled:opacity-50"
                                                >
                                                    {togglingPoints ? '…' : points_redeeming ? 'Remove' : 'Use points'}
                                                </button>
                                            </div>
                                            <FieldError message={errors.points} />
                                        </div>
                                    )}

                                    <div className="mt-5 border-t border-black/5 pt-5">
                                        <p className="text-sm font-semibold text-ink">Promo code</p>
                                        {points_redeeming ? (
                                            <p className="mt-2 text-xs leading-relaxed text-ink/55">
                                                Promo codes are unavailable while loyalty points are applied.
                                            </p>
                                        ) : promo_code ? (
                                            <div className="mt-2 flex items-center justify-between gap-3 rounded-xl border border-black/10 bg-mist/50 px-3 py-2.5">
                                                <p className="text-sm font-bold tracking-wide text-ink uppercase">
                                                    {promo_code}
                                                </p>
                                                <button
                                                    type="button"
                                                    onClick={removePromo}
                                                    className="min-h-11 cursor-pointer px-2 text-sm font-bold tracking-wide text-ink/50 uppercase transition hover:text-ink"
                                                >
                                                    Remove
                                                </button>
                                            </div>
                                        ) : (
                                            <div className="mt-2 flex flex-col gap-2 sm:flex-row">
                                                <input
                                                    type="text"
                                                    value={promoInput}
                                                    onChange={(event) => setPromoInput(event.target.value)}
                                                    onKeyDown={(event) => {
                                                        if (event.key === 'Enter') {
                                                            applyPromo(event);
                                                        }
                                                    }}
                                                    placeholder="ENTER PROMO CODE"
                                                    className="min-h-11 min-w-0 flex-1 rounded-xl border border-black/10 bg-white px-4 py-2.5 text-base tracking-wide text-ink uppercase outline-none transition placeholder:text-ink/35 focus:border-forest/40 sm:text-sm"
                                                    autoComplete="off"
                                                />
                                                <button
                                                    type="button"
                                                    onClick={applyPromo}
                                                    disabled={applyingPromo || !promoInput.trim()}
                                                    className="min-h-11 cursor-pointer rounded-xl bg-ink px-4 py-2.5 text-sm font-bold text-white transition hover:bg-leaf-800 disabled:cursor-not-allowed disabled:opacity-50"
                                                >
                                                    {applyingPromo ? '…' : 'Apply'}
                                                </button>
                                            </div>
                                        )}
                                        <FieldError message={errors.promo_code} />
                                    </div>

                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="mt-6 w-full cursor-pointer rounded-xl bg-ink px-6 py-3.5 text-sm font-bold tracking-wide text-white uppercase transition hover:bg-leaf-800 disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        {processing ? 'Placing order…' : 'Continue to payment'}
                                    </button>
                                    <p className="mt-3 text-center text-xs leading-relaxed text-ink/45">
                                        Next you&apos;ll pay by Mobile Money and upload your payment screenshot.
                                    </p>
                                </div>
                            </aside>
                        </>
                    )}
                </Form>
            </section>
        </ShopLayout>
    );
}
