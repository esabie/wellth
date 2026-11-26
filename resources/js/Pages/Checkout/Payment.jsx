import { Form, Link, usePage } from '@inertiajs/react';
import ShopLayout from '../../Layouts/ShopLayout';

function formatMoney(value) {
    return new Intl.NumberFormat('en-GH', {
        style: 'currency',
        currency: 'GHS',
    }).format(Number(value));
}

export default function Payment({ order }) {
    const { flash, errors } = usePage().props;
    const errorMessage = flash?.error || errors?.checkout || errors?.payment;

    return (
        <ShopLayout>
            <section className="mx-auto max-w-3xl px-4 py-10 sm:px-6 sm:py-12">
                <p className="text-sm text-ink/45">
                    <Link href="/shop" className="transition hover:text-ink">
                        Shop
                    </Link>
                    <span className="mx-1.5">/</span>
                    <span className="font-semibold text-ink">Payment</span>
                </p>

                <h1 className="mt-4 font-display text-4xl font-extrabold tracking-tight text-ink">
                    Complete your payment
                </h1>
                <p className="mt-3 max-w-2xl text-ink/65">
                    Pay securely with card or Mobile Money through Paystack. Amount due:{' '}
                    <span className="font-semibold text-ink">{formatMoney(order.total)}</span>.
                </p>

                {errorMessage && (
                    <p className="mt-6 rounded-xl bg-punch/10 px-4 py-3 text-sm font-semibold text-punch">
                        {errorMessage}
                    </p>
                )}

                <div className="mt-8 space-y-5">
                    <div className="rounded-2xl border border-black/10 bg-white p-5 sm:p-6">
                        <p className="text-[11px] font-bold tracking-[0.16em] text-ink/45 uppercase">Order summary</p>
                        <div className="mt-4 flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p className="text-xs font-bold tracking-wide text-ink/45 uppercase">Order number</p>
                                <p className="mt-1 font-mono text-base font-semibold tracking-wide break-all text-ink">
                                    {order.uuid}
                                </p>
                            </div>
                            <p className="font-display text-2xl font-extrabold text-ink">{formatMoney(order.total)}</p>
                        </div>

                        <ul className="mt-4 divide-y divide-black/5 border-t border-black/5">
                            {order.items.map((item) => (
                                <li
                                    key={`${item.product_name}-${item.quantity}`}
                                    className="flex justify-between gap-4 py-3 text-sm"
                                >
                                    <span className="text-ink">
                                        {item.product_name}{' '}
                                        <span className="text-ink/45">× {item.quantity}</span>
                                    </span>
                                    <span className="font-semibold text-ink">{formatMoney(item.line_total)}</span>
                                </li>
                            ))}
                        </ul>

                        {Number(order.service_fee_amount) > 0 && (
                            <div className="flex justify-between border-t border-black/5 pt-4 text-sm text-ink/65">
                                <span>Service fee</span>
                                <span className="font-semibold text-ink">{formatMoney(order.service_fee_amount)}</span>
                            </div>
                        )}

                        <Form
                            action={`/checkout/${order.uuid}/pay/start`}
                            method="post"
                            className="mt-6"
                        >
                            {({ processing }) => (
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full cursor-pointer rounded-xl bg-ink px-6 py-3.5 text-sm font-bold tracking-wide text-white uppercase transition hover:bg-leaf-800 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    {processing ? 'Redirecting…' : 'Continue to Paystack'}
                                </button>
                            )}
                        </Form>

                        <p className="mt-3 text-center text-xs leading-relaxed text-ink/45">
                            You&apos;ll be redirected to Paystack to finish payment, then returned here.
                        </p>
                    </div>
                </div>
            </section>
        </ShopLayout>
    );
}
