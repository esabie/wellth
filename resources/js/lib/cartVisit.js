/** Shared Inertia options so cart mutations don't remount the current page. */
export const cartMutationOptions = {
    preserveScroll: true,
    preserveState: true,
    only: ['cart', 'flash'],
};

/** Cart index also keeps its own page props in sync. */
export const cartPageMutationOptions = {
    ...cartMutationOptions,
    only: ['cart', 'flash', 'items', 'subtotal'],
};
