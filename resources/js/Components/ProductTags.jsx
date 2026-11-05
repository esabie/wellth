const styles = {
    new: 'bg-white px-3 py-1.5 text-[11px] tracking-[0.16em] text-ink shadow-[0_1px_2px_rgba(26,26,26,0.08),0_8px_18px_-10px_rgba(26,26,26,0.45)]',
    coming_soon: 'bg-white px-2.5 py-1 text-[10px] tracking-[0.14em] text-ink ring-1 ring-black/10',
};

export function isComingSoon(tags) {
    return tags?.some((tag) => tag.value === 'coming_soon') ?? false;
}

export default function ProductTags({ tags, className = '' }) {
    if (!tags?.length) {
        return null;
    }

    return (
        <div className={`flex flex-wrap gap-1.5 ${className}`}>
            {tags.map((tag) => (
                <span
                    key={tag.value}
                    className={`rounded-full font-bold uppercase ${styles[tag.value] ?? styles.new}`}
                >
                    {tag.label}
                </span>
            ))}
        </div>
    );
}
