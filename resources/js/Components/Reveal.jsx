import { useEffect, useRef, useState } from 'react';

export default function Reveal({
    as: Tag = 'div',
    children,
    className = '',
    variant = 'rise',
    delay = 0,
    once = false,
    ...props
}) {
    const ref = useRef(null);
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        const node = ref.current;

        if (!node) {
            return undefined;
        }

        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (reducedMotion) {
            setVisible(true);

            return undefined;
        }

        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting) {
                    setVisible(true);

                    if (once) {
                        observer.unobserve(node);
                    }

                    return;
                }

                if (!once) {
                    setVisible(false);
                }
            },
            {
                threshold: [0, 0.12, 0.2],
                rootMargin: '0px 0px -10% 0px',
            },
        );

        observer.observe(node);

        return () => observer.disconnect();
    }, [once]);

    return (
        <Tag
            ref={ref}
            className={`reveal reveal-${variant} ${visible ? 'is-visible' : ''} ${className}`}
            style={{ '--reveal-delay': `${delay}ms` }}
            {...props}
        >
            {children}
        </Tag>
    );
}
