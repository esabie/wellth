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

function optionPayload(item) {
    const payload = {};

    if (item?.lactose_intolerant !== null && item?.lactose_intolerant !== undefined) {
        payload.lactose_intolerant = item.lactose_intolerant;
    }

    if (item?.with_ginger !== null && item?.with_ginger !== undefined) {
        payload.with_ginger = item.with_ginger;
    }

    return payload;
}

function isDrink(item) {
    return Boolean(item?.product?.is_drink);
}

function unitCount(items) {
    return items.reduce((sum, item) => sum + item.quantity * packUnitsFor(item), 0);
}

function shotUnitCount(items) {
    return items.reduce((sum, item) => {
        if (isDrink(item)) {
            return sum;
        }

        return sum + item.quantity * packUnitsFor(item);
    }, 0);
}

function isDrinkOnlyCart(items) {
    return items.length > 0 && items.every(isDrink);
}

function minimumState(items, shopMinimum) {
    const drinkOnly = isDrinkOnlyCart(items);
    const minimumOrderQuantity = drinkOnly ? 1 : shopMinimum;
    const countTowardMinimum = drinkOnly ? unitCount(items) : shotUnitCount(items);

    return {
        minimum_order_quantity: minimumOrderQuantity,
        meets_minimum_order: countTowardMinimum >= minimumOrderQuantity,
        remaining_for_minimum_order: Math.max(0, minimumOrderQuantity - countTowardMinimum),
    };
}

/**
 * Optimistically rewrite the shared cart summary after a quantity change.
 */
export function cartWithQuantity(cart, key, quantity) {
    const current = cart ?? {};
    const shopMinimum = current.minimum_order_quantity_shop ?? current.minimum_order_quantity ?? 10;
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

    const count = unitCount(items);
    const subtotal = money(
        items.reduce((sum, item) => sum + Number(item.line_total), 0),
    );

    return {
        ...current,
        items,
        count,
        subtotal,
        minimum_order_quantity_shop: shopMinimum,
        is_drink_only: isDrinkOnlyCart(items),
        ...minimumState(items, shopMinimum),
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
    const shopMinimum = cart?.minimum_order_quantity_shop ?? cart?.minimum_order_quantity ?? 10;

    return {
        ...cart,
        items: [],
        count: 0,
        subtotal: '0.00',
        minimum_order_quantity_shop: shopMinimum,
        minimum_order_quantity: shopMinimum,
        meets_minimum_order: false,
        remaining_for_minimum_order: shopMinimum,
        is_drink_only: false,
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
            .patch(`/cart/${item.product.slug}`, { quantity, ...optionPayload(item) }, options);
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
            data: optionPayload(item),
        });
}
