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

function lineKey(item) {
    return item?.key ?? String(item?.product?.id ?? '');
}

function lactosePayload(item) {
    if (item?.lactose_intolerant === null || item?.lactose_intolerant === undefined) {
        return {};
    }

    return { lactose_intolerant: item.lactose_intolerant };
}

/**
 * Optimistically rewrite the shared cart summary after a quantity change.
 */
export function cartWithQuantity(cart, key, quantity) {
    const current = cart ?? {};
    const minimumOrderQuantity = current.minimum_order_quantity ?? 10;
    const nextQuantity = Math.max(0, Number(quantity) || 0);

    const items = (current.items ?? [])
        .map((item) => {
            if (lineKey(item) !== key) {
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
export function cartPageWithQuantity(props, key, quantity) {
    const cart = cartWithQuantity(props.cart, key, quantity);

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
export function syncCartQuantity(router, item, quantity, options = cartMutationOptions) {
    const key = lineKey(item);
    const existing = quantitySyncTimers.get(key);

    if (existing) {
        window.clearTimeout(existing);
    }

    const timer = window.setTimeout(() => {
        quantitySyncTimers.delete(key);

        // Cancel only in-flight async cart syncs, not the click itself.
        router.cancelAll({ async: true });

        router
            .optimistic((props) =>
                options.only?.includes('items')
                    ? cartPageWithQuantity(props, key, quantity)
                    : { cart: cartWithQuantity(props.cart, key, quantity) },
            )
            .patch(`/cart/${item.product.slug}`, { quantity, ...lactosePayload(item) }, options);
    }, 180);

    quantitySyncTimers.set(key, timer);
}

export function syncCartRemove(router, item, options = cartMutationOptions) {
    const key = lineKey(item);
    const existing = quantitySyncTimers.get(key);

    if (existing) {
        window.clearTimeout(existing);
        quantitySyncTimers.delete(key);
    }

    router
        .optimistic((props) =>
            options.only?.includes('items')
                ? cartPageWithQuantity(props, key, 0)
                : { cart: cartWithQuantity(props.cart, key, 0) },
        )
        .delete(`/cart/${item.product.slug}`, {
            ...options,
            data: lactosePayload(item),
        });
}
