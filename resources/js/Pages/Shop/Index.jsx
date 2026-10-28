import { Head, Link, router } from '@inertiajs/react';
import ProductCard from '../../Components/ProductCard';
import ShopLayout from '../../Layouts/ShopLayout';

export default function Index({ products, categories, filters }) {
    const selectCategory = (slug) => {
        router.get(
            '/shop',
            { category: slug || undefined },
            { preserveState: true, replace: true },
        );
    };

    const total = products.total ?? products.data.length;

    return (
        <ShopLayout>
            <Head title="Shop" />

            <section className="bg-[#f3efe6]">
                <div className="mx-auto max-w-6xl px-4 pt-10 pb-6 sm:px-6 sm:pt-14 sm:pb-8">
                    <p className="text-xs font-bold tracking-[0.2em] text-forest/50 uppercase">
                        Shop all
                    </p>
                    <div className="mt-3 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div className="max-w-2xl">
                            <h1 className="font-display text-4xl font-extrabold tracking-tight text-forest sm:text-5xl lg:text-[3.25rem]">
                                Choose your daily shot.
                            </h1>
                            <p className="mt-3 max-w-xl text-base leading-relaxed text-forest/75 sm:text-lg">
                                Organic wellness shots with bold flavour and clean purpose. Mix formulas freely —
                                checkout opens at 10 bottles.
                            </p>
                        </div>
                        <p className="shrink-0 text-sm font-medium text-forest/45">
                            {total} {total === 1 ? 'formula' : 'formulas'}
                        </p>
                    </div>

                    <div className="mt-8 flex gap-2 overflow-x-auto pb-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                        <button
                            type="button"
                            onClick={() => selectCategory(null)}
                            className={`shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition ${
                                !filters.category
                                    ? 'bg-ink text-white'
                                    : 'bg-white/70 text-forest/70 ring-1 ring-forest/10 hover:bg-white hover:text-forest'
                            }`}
                        >
                            All
                        </button>
                        {categories.map((category) => (
                            <button
                                key={category.id}
                                type="button"
                                onClick={() => selectCategory(category.slug)}
                                className={`shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition ${
                                    filters.category === category.slug
                                        ? 'bg-ink text-white'
                                        : 'bg-white/70 text-forest/70 ring-1 ring-forest/10 hover:bg-white hover:text-forest'
                                }`}
                            >
                                {category.name}
                            </button>
                        ))}
                    </div>
                </div>
            </section>

            <section className="bg-[#f3efe6] pb-16 sm:pb-20">
                <div className="mx-auto max-w-6xl px-4 sm:px-6">
                    {products.data.length === 0 ? (
                        <div className="border-t border-forest/10 py-20 text-center">
                            <p className="font-display text-2xl font-extrabold text-forest">Nothing here yet</p>
                            <p className="mt-2 text-forest/60">Try another category, or check back soon.</p>
                            <button
                                type="button"
                                onClick={() => selectCategory(null)}
                                className="mt-6 rounded-full bg-ink px-5 py-2.5 text-sm font-bold text-white transition hover:bg-leaf-800"
                            >
                                View all formulas
                            </button>
                        </div>
                    ) : (
                        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {products.data.map((product) => (
                                <ProductCard key={product.id} product={product} />
                            ))}
                        </div>
                    )}

                    {products.last_page > 1 && (
                        <div className="mt-12 flex justify-center gap-2">
                            {products.links
                                .filter((link) => link.url)
                                .map((link) => (
                                    <Link
                                        key={`${link.label}-${link.url}`}
                                        href={link.url}
                                        className={`rounded-full px-4 py-2 text-sm font-semibold transition ${
                                            link.active
                                                ? 'bg-ink text-white'
                                                : 'bg-white/70 text-forest/70 ring-1 ring-forest/10 hover:bg-white hover:text-forest'
                                        }`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                        </div>
                    )}
                </div>
            </section>
        </ShopLayout>
    );
}
