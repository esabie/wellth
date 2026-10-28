import { Head } from '@inertiajs/react';
import { useId, useState } from 'react';
import ShopLayout from '../Layouts/ShopLayout';

function FaqItem({ faq, index, open, onToggle }) {
    const panelId = useId();
    const buttonId = useId();

    return (
        <div
            className={`overflow-hidden rounded-2xl bg-white transition ${
                open ? 'ring-2 ring-forest/30' : 'ring-1 ring-black/8'
            }`}
        >
            <button
                id={buttonId}
                type="button"
                aria-expanded={open}
                aria-controls={panelId}
                onClick={() => onToggle(index)}
                className="flex w-full cursor-pointer items-center justify-between gap-4 px-5 py-4 text-left sm:px-6 sm:py-5"
            >
                <span className="text-base font-semibold text-ink sm:text-lg">{faq.question}</span>
                <svg
                    aria-hidden="true"
                    viewBox="0 0 20 20"
                    fill="none"
                    className={`h-5 w-5 shrink-0 text-ink/50 transition ${open ? 'rotate-180' : ''}`}
                >
                    <path
                        d="M5 7.5 10 12.5 15 7.5"
                        stroke="currentColor"
                        strokeWidth="1.8"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    />
                </svg>
            </button>

            {open && (
                <div id={panelId} role="region" aria-labelledby={buttonId} className="px-5 pb-5 sm:px-6 sm:pb-6">
                    <p className="max-w-3xl text-sm leading-relaxed text-ink/60 sm:text-base">{faq.answer}</p>
                </div>
            )}
        </div>
    );
}

export default function Faq({ faqs }) {
    const [openIndex, setOpenIndex] = useState(0);

    const toggle = (index) => {
        setOpenIndex((current) => (current === index ? null : index));
    };

    return (
        <ShopLayout>
            <Head title="FAQs" />

            <section className="bg-mist px-4 py-14 sm:px-6 sm:py-20">
                <div className="mx-auto max-w-3xl text-center">
                    <p className="text-xs font-bold tracking-[0.2em] text-forest uppercase">Questions</p>
                    <h1 className="mt-3 font-display text-4xl font-extrabold tracking-tight text-ink sm:text-5xl">
                        Frequently Asked
                    </h1>
                    <p className="mt-3 text-base text-ink/55 sm:text-lg">
                        Everything you need to know about Wellth shots.
                    </p>
                </div>

                <div className="mx-auto mt-10 flex max-w-3xl flex-col gap-3 sm:mt-12 sm:gap-4">
                    {faqs.map((faq, index) => (
                        <FaqItem
                            key={faq.question}
                            faq={faq}
                            index={index}
                            open={openIndex === index}
                            onToggle={toggle}
                        />
                    ))}
                </div>
            </section>
        </ShopLayout>
    );
}
