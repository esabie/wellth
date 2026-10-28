import { Head, Link } from '@inertiajs/react';
import ShopLayout from '../../Layouts/ShopLayout';

function formatMoney(value) {
    return new Intl.NumberFormat('en-GH', {
        style: 'currency',
        currency: 'GHS',
    }).format(Number(value));
}

export default function Confirmation({ order }) {
    const awaitingConfirmation = order.status === 'awaiting_confirmation';

    return (
        <ShopLayout>
            <Head title={awaitingConfirmation ? 'Payment received' : 'Order confirmed'} />

            <section className="mx-auto max-w-3xl px-4 py-12 sm:px-6 sm:py-16">
                <p className="text-xs font-bold tracking-[0.18em] text-forest/50 uppercase">Thank you</p>
                <h1 className="mt-3 font-display text-4xl font-extrabold tracking-tight text-ink sm:text-5xl">
                    {awaitingConfirmation ? 'Screenshot received.' : 'Order confirmed.'}
                </h1>
                <p className="mt-3 max-w-xl text-ink/65">
                    {awaitingConfirmation
                        ? `Thanks, ${order.customer_name}. We're reviewing your MoMo payment proof and will email ${order.customer_email} and text ${order.customer_phone} once it's confirmed. Please allow up to one week after confirmation for preparation and delivery.`
                        : `We've confirmed your payment, ${order.customer_name}. A confirmation email and SMS are on the way. Shots are made fresh — please allow up to one week for delivery.`}
                </p>

                <div className="mt-10 rounded-[1.5rem] bg-white p-6 ring-1 ring-black/5 sm:p-8">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p className="text-xs font-bold tracking-wide text-ink/45 uppercase">Order number</p>
                            <p className="mt-1 font-mono text-base font-semibold tracking-wide text-ink">
                                {order.uuid}
                            </p>
                            {order.payment_reference && order.payment_reference !== order.uuid && (
                                <p className="mt-2 text-xs text-ink/50">
                                    Payment reference:{' '}
                                    <span className="font-mono font-semibold text-ink">{order.payment_reference}</span>
                                </p>
                            )}
                        </div>
                        <p className="font-display text-2xl font-extrabold text-ink">{formatMoney(order.total)}</p>
                    </div>

                    <ul className="mt-6 divide-y divide-black/5 border-t border-black/5">
                        {order.items.map((item) => (
                            <li key={`${item.product_name}-${item.quantity}`} className="flex justify-between gap-4 py-3">
                                <div>
                                    <p className="font-semibold text-ink">{item.product_name}</p>
                                    <p className="text-xs text-ink/50">Qty {item.quantity}</p>
                                </div>
                                <p className="text-sm font-bold text-ink">{formatMoney(item.line_total)}</p>
                            </li>
                        ))}
                    </ul>

                    <div className="mt-6 border-t border-black/5 pt-5 text-sm text-ink/70">
                        <p className="font-semibold text-ink">Delivering to</p>
                        <p className="mt-1">{order.shipping_line1}</p>
                        {order.shipping_line2 && <p>{order.shipping_line2}</p>}
                        <p>
                            {order.shipping_city}
                            {order.shipping_region ? `, ${order.shipping_region}` : ''}
                        </p>
                    </div>
                </div>

                <Link
                    href="/shop"
                    className="mt-8 inline-flex cursor-pointer rounded-full bg-ink px-6 py-3 text-sm font-bold text-white transition hover:bg-leaf-800"
                >
                    Back to shop
                </Link>
            </section>
        </ShopLayout>
    );
}
