import { Form, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import ShopLayout from '../../Layouts/ShopLayout';

const STEPS = 3;

function FieldError({ message }) {
    if (!message) {
        return null;
    }

    return <p className="mt-2 text-xs font-semibold text-punch">{message}</p>;
}

function toggleTag(tags, value) {
    if (value === 'all_good') {
        return tags.includes('all_good') ? [] : ['all_good'];
    }

    const withoutAllGood = tags.filter((tag) => tag !== 'all_good');

    return withoutAllGood.includes(value)
        ? withoutAllGood.filter((tag) => tag !== value)
        : [...withoutAllGood, value];
}

function Progress({ step }) {
    return (
        <div className="flex items-center gap-2" aria-label={`Step ${step} of ${STEPS}`}>
            {Array.from({ length: STEPS }, (_, index) => {
                const current = index + 1;

                return (
                    <span
                        key={current}
                        className={`h-1.5 flex-1 rounded-full transition-colors duration-300 ${
                            current <= step ? 'bg-forest' : 'bg-black/10'
                        }`}
                    />
                );
            })}
        </div>
    );
}

function StepShell({ children, stepKey }) {
    return (
        <div key={stepKey} className="feedback-step">
            {children}
        </div>
    );
}

export default function Show({
    token,
    order,
    products,
    includes_bouquet: includesBouquet,
    shopping_ratings: shoppingRatings,
    improvement_tags: improvementTagOptions,
    submitted,
    feedback,
}) {
    const { flash, errors } = usePage().props;
    const justSubmitted = Boolean(flash?.feedback_received) || submitted;
    const [step, setStep] = useState(1);
    const [productId, setProductId] = useState('');
    const [rating, setRating] = useState('');
    const [tags, setTags] = useState([]);
    const [note, setNote] = useState('');

    const selectedProduct = products.find((product) => String(product.id) === String(productId));
    const selectedRating = shoppingRatings.find((option) => String(option.value) === String(rating));

    const stepCopy = {
        1: {
            title: `${order.first_name}, which blend did you love most?`,
            body: includesBouquet
                ? 'Your bouquet includes these blends. Select the blend you loved the most.'
                : 'Select the blend you loved the most.',
        },
        2: {
            title: 'How did shopping feel?',
            body: selectedProduct
                ? `Glad you liked ${selectedProduct.name}. Now the website itself.`
                : 'Finding blends, cart, checkout, payment…',
        },
        3: {
            title: 'Anything we could do better?',
            body: 'Select what matters. A note is optional, but we\'d love to hear your thoughts.',
        },
    };

    return (
        <ShopLayout>
            <section className="mx-auto max-w-2xl px-4 py-12 sm:px-6 sm:py-16">
                {justSubmitted ? (
                    <>
                        <p className="text-xs font-bold tracking-[0.18em] text-forest/50 uppercase">Feedback</p>
                        <h1 className="mt-3 font-display text-4xl font-extrabold tracking-tight text-ink sm:text-5xl">
                            Thank you, {order.first_name}.
                        </h1>
                        <p className="mt-3 max-w-xl text-ink/65">
                            That took seconds. We value your input as it helps us refine our products and enhance your shopping experience.
                        </p>

                        {feedback && (
                            <div className="mt-10 space-y-5 rounded-[1.5rem] bg-white p-6 ring-1 ring-black/5 sm:p-8">
                                <div>
                                    <p className="text-xs font-bold tracking-wide text-ink/45 uppercase">
                                        Favourite blend
                                    </p>
                                    <p className="mt-1 font-semibold text-ink">{feedback.favorite_product}</p>
                                </div>
                                <div>
                                    <p className="text-xs font-bold tracking-wide text-ink/45 uppercase">Shopping experience</p>
                                    <p className="mt-1 font-semibold text-ink">
                                        {feedback.shopping_rating}/5 · {feedback.shopping_label}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs font-bold tracking-wide text-ink/45 uppercase">
                                        Could improve
                                    </p>
                                    <p className="mt-1 text-sm font-semibold text-ink/80">
                                        {feedback.improvement_labels.join(' · ')}
                                    </p>
                                </div>
                                {feedback.note && (
                                    <div>
                                        <p className="text-xs font-bold tracking-wide text-ink/45 uppercase">Note</p>
                                        <p className="mt-1 whitespace-pre-wrap text-sm leading-relaxed text-ink/80">
                                            {feedback.note}
                                        </p>
                                    </div>
                                )}
                            </div>
                        )}
                    </>
                ) : (
                    <>
                        <Progress step={step} />

                        <p className="mt-6 text-xs font-bold tracking-[0.18em] text-forest/50 uppercase">
                            {step} of {STEPS} · Order {order.uuid}
                        </p>
                        <h1 className="mt-3 font-display text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">
                            {stepCopy[step].title}
                        </h1>
                        <p className="mt-3 max-w-xl text-ink/65">{stepCopy[step].body}</p>

                        {errors?.feedback && (
                            <p className="mt-6 rounded-xl bg-punch/10 px-4 py-3 text-sm font-semibold text-punch">
                                {errors.feedback}
                            </p>
                        )}

                        <Form
                            action={`/feedback/${token}`}
                            method="post"
                            className="mt-8"
                            onError={() => setStep(3)}
                        >
                            {({ processing, errors: formErrors }) => (
                                <>
                                    <input type="hidden" name="product_id" value={productId} />
                                    <input type="hidden" name="shopping_rating" value={rating} />
                                    {tags.map((tag) => (
                                        <input key={tag} type="hidden" name="improvement_tags[]" value={tag} />
                                    ))}
                                    <input type="hidden" name="note" value={note} />

                                    <div className="rounded-[1.5rem] bg-white p-5 ring-1 ring-black/5 sm:p-7">
                                        {step === 1 && (
                                            <StepShell stepKey="blend">
                                                <div className="grid gap-3 sm:grid-cols-2">
                                                    {products.map((product) => {
                                                        const selected = String(productId) === String(product.id);

                                                        return (
                                                            <button
                                                                key={product.id}
                                                                type="button"
                                                                onClick={() => {
                                                                    setProductId(String(product.id));
                                                                    window.setTimeout(() => setStep(2), 180);
                                                                }}
                                                                className={`flex cursor-pointer items-center gap-3 rounded-2xl border px-3 py-3 text-left transition ${
                                                                    selected
                                                                        ? 'border-forest bg-forest/5 shadow-[0_10px_24px_-16px_rgba(31,61,42,0.45)]'
                                                                        : 'border-black/8 hover:border-forest/30'
                                                                }`}
                                                            >
                                                                <span className="h-14 w-14 shrink-0 overflow-hidden rounded-xl bg-[#f3efe6]">
                                                                    {product.image ? (
                                                                        <img
                                                                            src={product.image}
                                                                            alt=""
                                                                            className="h-full w-full object-cover"
                                                                        />
                                                                    ) : null}
                                                                </span>
                                                                <span className="text-sm font-bold text-ink">
                                                                    {product.name}
                                                                </span>
                                                            </button>
                                                        );
                                                    })}
                                                </div>
                                                <FieldError message={formErrors.product_id} />
                                            </StepShell>
                                        )}

                                        {step === 2 && (
                                            <StepShell stepKey="shop">
                                                <div className="space-y-2.5">
                                                    {shoppingRatings.map((option) => {
                                                        const selected = String(rating) === String(option.value);

                                                        return (
                                                            <button
                                                                key={option.value}
                                                                type="button"
                                                                onClick={() => {
                                                                    setRating(String(option.value));
                                                                    window.setTimeout(() => setStep(3), 180);
                                                                }}
                                                                className={`flex w-full cursor-pointer items-center justify-between rounded-2xl border px-4 py-3.5 text-left transition ${
                                                                    selected
                                                                        ? 'border-forest bg-forest/5'
                                                                        : 'border-black/8 hover:border-forest/30'
                                                                }`}
                                                            >
                                                                <span className="text-sm font-bold text-ink">
                                                                    {option.label}
                                                                </span>
                                                                <span className="font-display text-lg font-extrabold text-ink/35">
                                                                    {option.value}
                                                                </span>
                                                            </button>
                                                        );
                                                    })}
                                                </div>
                                                <FieldError message={formErrors.shopping_rating} />
                                                <button
                                                    type="button"
                                                    onClick={() => setStep(1)}
                                                    className="mt-5 cursor-pointer text-sm font-semibold text-ink/45 transition hover:text-ink"
                                                >
                                                    ← Back
                                                </button>
                                            </StepShell>
                                        )}

                                        {step === 3 && (
                                            <StepShell stepKey="improve">
                                                {(selectedProduct || selectedRating) && (
                                                    <div className="mb-5 flex flex-wrap gap-2 text-xs font-semibold text-ink/55">
                                                        {selectedProduct && (
                                                            <span className="rounded-full bg-[#f3efe6] px-3 py-1">
                                                                Favourite: {selectedProduct.name}
                                                            </span>
                                                        )}
                                                        {selectedRating && (
                                                            <span className="rounded-full bg-[#f3efe6] px-3 py-1">
                                                                Shop: {selectedRating.label}
                                                            </span>
                                                        )}
                                                    </div>
                                                )}

                                                <div className="flex flex-wrap gap-2">
                                                    {improvementTagOptions.map((option) => {
                                                        const selected = tags.includes(option.value);

                                                        return (
                                                            <button
                                                                key={option.value}
                                                                type="button"
                                                                onClick={() =>
                                                                    setTags((current) =>
                                                                        toggleTag(current, option.value),
                                                                    )
                                                                }
                                                                className={`cursor-pointer rounded-full px-4 py-2.5 text-sm font-semibold transition ${
                                                                    selected
                                                                        ? 'bg-forest text-white'
                                                                        : 'bg-[#f3efe6] text-ink hover:bg-forest/10'
                                                                }`}
                                                            >
                                                                {option.label}
                                                            </button>
                                                        );
                                                    })}
                                                </div>
                                                <FieldError
                                                    message={
                                                        formErrors.improvement_tags ||
                                                        formErrors['improvement_tags.0']
                                                    }
                                                />

                                                <label className="mt-6 block">
                                                    <span className="text-xs font-bold tracking-wide text-ink/45 uppercase">
                                                        One more thought? Optional
                                                    </span>
                                                    <textarea
                                                        rows={2}
                                                        maxLength={500}
                                                        value={note}
                                                        onChange={(event) => setNote(event.target.value)}
                                                        placeholder="Taste, timing, packaging… one line is plenty."
                                                        className="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3 text-sm text-ink outline-none transition focus:border-forest/40"
                                                    />
                                                    <FieldError message={formErrors.note} />
                                                </label>

                                                <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
                                                    <button
                                                        type="submit"
                                                        disabled={
                                                            processing ||
                                                            !productId ||
                                                            !rating ||
                                                            tags.length === 0
                                                        }
                                                        className="w-full cursor-pointer rounded-xl bg-ink px-6 py-3.5 text-sm font-bold tracking-wide text-white uppercase transition hover:bg-leaf-800 disabled:cursor-not-allowed disabled:opacity-60 sm:flex-1"
                                                    >
                                                        {processing ? 'Sending…' : 'Send feedback'}
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => setStep(2)}
                                                        className="cursor-pointer text-sm font-semibold text-ink/45 transition hover:text-ink sm:px-2"
                                                    >
                                                        ← Back
                                                    </button>
                                                </div>
                                            </StepShell>
                                        )}
                                    </div>
                                </>
                            )}
                        </Form>
                    </>
                )}

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
