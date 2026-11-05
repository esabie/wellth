import { usePage } from '@inertiajs/react';

export default function Logo({ className = '', tone = 'light' }) {
    const assets = usePage().props.assets ?? {};
    const src =
        tone === 'dark'
            ? (assets.logoDark ?? '/images/logo-dark.png')
            : (assets.logo ?? '/images/logo.png');

    return (
        <img
            src={src}
            alt="Wellth"
            width={640}
            height={560}
            className={className}
        />
    );
}
