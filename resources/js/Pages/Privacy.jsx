import ShopLayout from '../Layouts/ShopLayout';

function Section({ section }) {
    return (
        <section className="space-y-4">
            <h2 className="font-display text-2xl font-extrabold tracking-tight text-ink sm:text-3xl">
                {section.heading}
            </h2>

            {section.paragraphs?.map((paragraph) => (
                <p key={paragraph} className="text-base leading-relaxed text-ink/65">
                    {paragraph}
                </p>
            ))}

            {section.items?.length > 0 && (
                <ul className="list-disc space-y-2 pl-5 text-base leading-relaxed text-ink/65">
                    {section.items.map((item) => (
                        <li key={item}>{item}</li>
                    ))}
                </ul>
            )}

            {section.subsections?.map((subsection) => (
                <div key={subsection.heading} className="space-y-3 pt-2">
                    <h3 className="text-lg font-bold text-ink">{subsection.heading}</h3>
                    {subsection.paragraphs?.map((paragraph) => (
                        <p key={paragraph} className="text-base leading-relaxed text-ink/65">
                            {paragraph}
                        </p>
                    ))}
                    {subsection.items?.length > 0 && (
                        <ul className="list-disc space-y-2 pl-5 text-base leading-relaxed text-ink/65">
                            {subsection.items.map((item) => (
                                <li key={item}>{item}</li>
                            ))}
                        </ul>
                    )}
                </div>
            ))}
        </section>
    );
}

export default function Privacy({ updated, intro, sections }) {
    return (
        <ShopLayout>
            <section className="bg-mist px-4 py-14 sm:px-6 sm:py-20">
                <div className="mx-auto max-w-3xl">
                    <p className="text-xs font-bold tracking-[0.2em] text-forest uppercase">Legal</p>
                    <h1 className="mt-3 font-display text-4xl font-extrabold tracking-tight text-ink sm:text-5xl">
                        Privacy Policy
                    </h1>
                    <p className="mt-3 text-sm text-ink/45">Last updated: {updated}</p>
                    <p className="mt-6 text-base leading-relaxed text-ink/65 sm:text-lg">{intro}</p>

                    <div className="mt-12 space-y-12">
                        {sections.map((section) => (
                            <Section key={section.heading} section={section} />
                        ))}
                    </div>
                </div>
            </section>
        </ShopLayout>
    );
}
