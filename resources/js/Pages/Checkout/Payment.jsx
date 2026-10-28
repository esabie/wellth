import { Form, Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
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

export default function Payment({ order, momo }) {
    const { errors } = usePage().props;
    const [fileName, setFileName] = useState('');

    return (
        <ShopLayout>
            <Head title="Pay by Mobile Money" />

            <section className="mx-auto max-w-3xl px-4 py-10 sm:px-6 sm:py-12">
                <p className="text-sm text-ink/45">
                    <Link href="/shop" className="transition hover:text-ink">
                        Shop
                    </Link>
                    <span className="mx-1.5">/</span>
                    <span className="font-semibold text-ink">Payment</span>
                </p>

                <h1 className="mt-4 font-display text-4xl font-extrabold tracking-tight text-ink">
                    Pay by Mobile Money
                </h1>
                <p className="mt-3 max-w-2xl text-ink/65">
                    Send exactly <span className="font-semibold text-ink">{formatMoney(order.total)}</span> to the
                    number below, then upload a screenshot of the confirmation.
                </p>

                {(errors.checkout || errors.payment_proof) && (
                    <p className="mt-6 rounded-xl bg-punch/10 px-4 py-3 text-sm font-semibold text-punch">
                        {errors.checkout || errors.payment_proof}
                    </p>
                )}

                <div className="mt-8 space-y-5">
                    <div className="rounded-2xl border border-black/8 bg-white p-5 sm:p-6">
                        <p className="text-[11px] font-bold tracking-[0.16em] text-ink/45 uppercase">
                            Payment details
                        </p>
                        <dl className="mt-4 space-y-4 text-sm">
                            <div className="flex flex-wrap items-baseline justify-between gap-2">
                                <dt className="text-ink/55">Network</dt>
                                <dd className="font-semibold text-ink">{momo.network}</dd>
                            </div>
                            <div className="flex flex-wrap items-baseline justify-between gap-2">
                                <dt className="text-ink/55">Account name</dt>
                                <dd className="font-semibold text-ink">{momo.account_name}</dd>
                            </div>
                            <div className="flex flex-wrap items-baseline justify-between gap-2">
                                <dt className="text-ink/55">MoMo number</dt>
                                <dd className="font-display text-2xl font-extrabold tracking-tight text-ink">
                                    {momo.display_number}
                                </dd>
                            </div>
                            <div className="flex flex-wrap items-baseline justify-between gap-2 border-t border-black/5 pt-4">
                                <dt className="text-ink/55">Amount</dt>
                                <dd className="font-display text-xl font-extrabold text-ink">
                                    {formatMoney(order.total)}
                                </dd>
                            </div>
                            <div className="rounded-xl bg-[#f7f4ef] px-4 py-3">
                                <p className="text-[11px] font-bold tracking-[0.14em] text-forest/55 uppercase">
                                    Use this as your MoMo reference / name
                                </p>
                                <p className="mt-1 font-mono text-lg font-bold tracking-wide text-forest">
                                    {order.payment_reference}
                                </p>
                            </div>
                        </dl>
                    </div>

                    <div className="rounded-2xl border border-black/8 bg-white p-5 sm:p-6">
                        <h2 className="font-display text-xl font-extrabold tracking-tight text-ink">
                            Upload payment screenshot
                        </h2>
                        <p className="mt-2 text-sm text-ink/60">
                            After you send the money, upload a clear screenshot of the MoMo confirmation. We&apos;ll
                            verify it and email and text you once the order is confirmed.
                        </p>

                        <Form
                            action={`/checkout/${order.uuid}/pay`}
                            method="post"
                            encType="multipart/form-data"
                            className="mt-5 space-y-4"
                        >
                            {({ processing, errors: formErrors }) => (
                                <>
                                    <label className="block">
                                        <span className="text-xs font-bold tracking-wide text-ink/50 uppercase">
                                            Screenshot
                                        </span>
                                        <input
                                            type="file"
                                            name="payment_proof"
                                            accept="image/jpeg,image/png,image/webp"
                                            required
                                            onChange={(event) =>
                                                setFileName(event.target.files?.[0]?.name ?? '')
                                            }
                                            className="mt-1.5 block w-full text-sm text-ink file:mr-4 file:cursor-pointer file:rounded-full file:border-0 file:bg-ink file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-leaf-800"
                                        />
                                        {fileName && (
                                            <p className="mt-2 text-xs font-semibold text-ink/55">Selected: {fileName}</p>
                                        )}
                                        <FieldError message={formErrors.payment_proof} />
                                    </label>

                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="w-full cursor-pointer rounded-xl bg-ink px-6 py-3.5 text-sm font-bold tracking-wide text-white uppercase transition hover:bg-leaf-800 disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        {processing ? 'Uploading…' : 'I have paid. Submit screenshot'}
                                    </button>
                                </>
                            )}
                        </Form>
                    </div>

                    <div className="rounded-2xl border border-black/8 bg-white p-5 sm:p-6">
                        <p className="text-xs font-bold tracking-wide text-ink/45 uppercase">Order number</p>
                        <p className="mt-1 font-mono text-base font-semibold tracking-wide text-ink">{order.uuid}</p>
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
                    </div>
                </div>
            </section>
        </ShopLayout>
    );
}
