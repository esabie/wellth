export default function PreorderNotice({ className = '' }) {
    return (
        <aside
            className={`rounded-2xl bg-[#f7f4ef] p-5 ring-1 ring-forest/10 ${className}`.trim()}
            aria-label="Preorder and freshness notice"
        >
            <p className="text-[11px] font-bold tracking-[0.16em] text-forest/55 uppercase">
                Made Fresh to Order
            </p>
            <p className="mt-2 text-sm leading-relaxed text-forest/85">
                Our natural shots are prepared on a preorder basis to maintain freshness and quality.
                Please allow up to one week for your order to be made and delivered to you.
            </p>
            <p className="mt-3 text-sm font-semibold text-forest">Minimum order: 10 shots.</p>
            <p className="mt-1 text-xs leading-relaxed text-ink/55">
                Mix formulas freely — checkout unlocks once your cart has at least 10 bottles.
            </p>
        </aside>
    );
}
