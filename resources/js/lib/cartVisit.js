/** Shared Inertia options so cart mutations don't remount the current page. */
export const cartMutationOptions = {
    preserveScroll: true,
    preserveState: true,
    only: ['cart', 'flash'],
    async: true,
    showProgress: false,
};

/** Cart index also keeps its own page props in sync. */
export const cartPageMutationOptions = {
    ...cartMutationOptions,
    only: ['cart', 'flash', 'items', 'subtotal'],
};

const quantitySyncTimers = new Map();

function money(value) {
    return Number(value).toFixed(2);
}

function packUnitsFor(item) {
    return Math.max(1, Number(item?.product?.pack_units ?? 1));
}

/**
 * Optimistically rewrite the shared cart summary after a quantity change.
 */
export function cartWithQuantity(cart, slug, quantity) {
    const current = cart ?? {};
    const minimumOrderQuantity = current.minimum_order_quantity ?? 10;
    const nextQuantity = Math.max(0, Number(quantity) || 0);

    const items = (current.items ?? [])
        .map((item) => {
            if (item.product?.slug !== slug) {
                return item;
            }

            if (nextQuantity <= 0) {
                return null;
            }

            return {
                ...item,
                quantity: nextQuantity,
                line_total: money(Number(item.product.price) * nextQuantity),
            };
        })
        .filter(Boolean);

    const count = items.reduce((sum, item) => sum + item.quantity * packUnitsFor(item), 0);
    const subtotal = money(
        items.reduce((sum, item) => sum + Number(item.line_total), 0),
    );

    return {
        ...current,
        items,
        count,
        subtotal,
        meets_minimum_order: count >= minimumOrderQuantity,
        remaining_for_minimum_order: Math.max(0, minimumOrderQuantity - count),
    };
}

/**
 * Cart page props mirror of the shared cart items/subtotal.
 */
export function cartPageWithQuantity(props, slug, quantity) {
    const cart = cartWithQuantity(props.cart, slug, quantity);

    return {
        cart,
        items: cart.items,
        subtotal: cart.subtotal,
    };
}

export function emptyCartSummary(cart) {
    return {
        ...cart,
        items: [],
        count: 0,
        subtotal: '0.00',
        meets_minimum_order: false,
        remaining_for_minimum_order: cart?.minimum_order_quantity ?? 10,
    };
}

/**
 * Debounced server sync so rapid +/- clicks stay on the latest quantity.
 */
export function syncCartQuantity(router, slug, quantity, options = cartMutationOptions) {
    const existing = quantitySyncTimers.get(slug);

    if (existing) {
        window.clearTimeout(existing);
    }

    const timer = window.setTimeout(() => {
        quantitySyncTimers.delete(slug);

        // Cancel only in-flight async cart syncs, not the click itself.
        router.cancelAll({ async: true });

        router
            .optimistic((props) =>
                options.only?.includes('items')
                    ? cartPageWithQuantity(props, slug, quantity)
                    : { cart: cartWithQuantity(props.cart, slug, quantity) },
            )
            .patch(`/cart/${slug}`, { quantity }, options);
    }, 180);

    quantitySyncTimers.set(slug, timer);
}

export function syncCartRemove(router, slug, options = cartMutationOptions) {
    const existing = quantitySyncTimers.get(slug);

    if (existing) {
        window.clearTimeout(existing);
        quantitySyncTimers.delete(slug);
    }

    router
        .optimistic((props) =>
            options.only?.includes('items')
                ? cartPageWithQuantity(props, slug, 0)
                : { cart: cartWithQuantity(props.cart, slug, 0) },
        )
        .delete(`/cart/${slug}`, options);
}
