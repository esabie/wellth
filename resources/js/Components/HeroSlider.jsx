import { useEffect, useEffectEvent, useState } from 'react';

const SLIDES = [
    {
        src: '/images/hero-shots-wide.jpg',
        mobileSrc: '/images/hero-shots.jpg',
        alt: 'Fresh citrus wellness shot bottle among oranges, lemons, limes, and passion fruit',
        position: 'object-[center_28%] sm:object-[center_32%] lg:object-[72%_42%]',
    },
    {
        src: '/images/hero-ginger-shot.jpg',
        alt: 'Ginger and lemon wellness shot with fresh ginger root and cayenne',
        position: 'object-[68%_center] lg:object-[75%_center]',
    },
    {
        src: '/images/hero-ruby-shot.jpg',
        alt: 'Ruby beetroot and pomegranate wellness shot with fresh ingredients',
        position: 'object-[70%_center] lg:object-[78%_center]',
    },
    {
        src: '/images/hero-green-shot.jpg',
        alt: 'Green apple and cucumber wellness shot with mint leaves',
        position: 'object-[68%_center] lg:object-[74%_center]',
    },
];

const INTERVAL_MS = 3500;

export default function HeroSlider() {
    const [index, setIndex] = useState(0);

    const goTo = useEffectEvent((next) => {
        setIndex((next + SLIDES.length) % SLIDES.length);
    });

    const advance = useEffectEvent(() => {
        setIndex((current) => (current + 1) % SLIDES.length);
    });

    useEffect(() => {
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (reducedMotion) {
            return undefined;
        }

        const timer = window.setInterval(() => {
            advance();
        }, INTERVAL_MS);

        return () => window.clearInterval(timer);
    }, []);

    return (
        <div className="absolute inset-0">
            {SLIDES.map((slide, slideIndex) => {
                const active = slideIndex === index;

                return (
                    <div
                        key={slide.src}
                        className={`absolute inset-0 transition-opacity duration-[2800ms] ease-[cubic-bezier(0.33,0,0.2,1)] ${
                            active ? 'opacity-100' : 'opacity-0'
                        }`}
                        aria-hidden={!active}
                    >
                        <picture>
                            {slide.mobileSrc && (
                                <source media="(max-width: 1023px)" srcSet={slide.mobileSrc} />
                            )}
                            <img
                                src={slide.src}
                                alt={slide.alt}
                                className={`hero-slide-image h-full w-full object-cover ${slide.position} ${
                                    active ? 'is-active' : ''
                                }`}
                                decoding="async"
                                fetchPriority={slideIndex === 0 ? 'high' : 'low'}
                                loading={slideIndex === 0 ? 'eager' : 'lazy'}
                            />
                        </picture>
                    </div>
                );
            })}

            <div
                aria-hidden="true"
                className="absolute inset-0 bg-gradient-to-t from-[#f4efe7] via-[#f4efe7]/55 to-[#f4efe7]/20 lg:bg-gradient-to-r lg:from-[#f4efe7] lg:via-[#f4efe7]/88 lg:to-transparent"
            />
            <div aria-hidden="true" className="absolute inset-y-0 left-0 hidden w-[48%] bg-[#f4efe7]/35 lg:block" />

            <div className="absolute inset-x-0 top-[5.5rem] z-10 flex justify-center gap-2 sm:top-auto sm:bottom-8 lg:justify-end lg:pr-10">
                <div className="flex items-center gap-1 rounded-full bg-white/70 px-2 py-1.5 backdrop-blur-sm ring-1 ring-forest/10 sm:gap-2 sm:px-3 sm:py-2">
                    {SLIDES.map((slide, slideIndex) => {
                        const active = slideIndex === index;

                        return (
                            <button
                                key={slide.src}
                                type="button"
                                aria-label={`Show slide ${slideIndex + 1}`}
                                aria-current={active ? 'true' : undefined}
                                onClick={() => goTo(slideIndex)}
                                className="flex min-h-11 min-w-11 items-center justify-center"
                            >
                                <span
                                    className={`block h-2.5 rounded-full transition-all duration-500 ${
                                        active ? 'w-7 bg-forest' : 'w-2.5 bg-forest/30'
                                    }`}
                                />
                            </button>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}
