import { useEffect } from 'react';
import { usePage } from '@inertiajs/react';

function upsertMeta(attribute, key, content) {
    const selector = `meta[${attribute}="${key}"]`;
    let element = document.head.querySelector(selector);

    if (!element) {
        element = document.createElement('meta');
        element.setAttribute(attribute, key);
        document.head.appendChild(element);
    }

    element.setAttribute('content', content);
}

function upsertCanonical(href) {
    let element = document.head.querySelector('link[rel="canonical"]');

    if (!element) {
        element = document.createElement('link');
        element.setAttribute('rel', 'canonical');
        document.head.appendChild(element);
    }

    element.setAttribute('href', href);
}

function upsertJsonLd(data) {
    let element = document.getElementById('wellth-jsonld');

    if (!data) {
        element?.remove();

        return;
    }

    if (!element) {
        element = document.createElement('script');
        element.id = 'wellth-jsonld';
        element.type = 'application/ld+json';
        document.head.appendChild(element);
    }

    element.textContent = JSON.stringify(data);
}

export default function Seo() {
    const { seo } = usePage().props;

    useEffect(() => {
        if (!seo) {
            return;
        }

        document.title = seo.title;
        upsertMeta('name', 'description', seo.description);
        upsertMeta('name', 'robots', seo.robots);
        upsertCanonical(seo.canonical);
        upsertMeta('property', 'og:site_name', seo.siteName);
        upsertMeta('property', 'og:title', seo.title);
        upsertMeta('property', 'og:description', seo.description);
        upsertMeta('property', 'og:type', seo.type);
        upsertMeta('property', 'og:url', seo.canonical);
        upsertMeta('property', 'og:image', seo.image);
        upsertMeta('name', 'twitter:card', 'summary_large_image');
        upsertMeta('name', 'twitter:title', seo.title);
        upsertMeta('name', 'twitter:description', seo.description);
        upsertMeta('name', 'twitter:image', seo.image);
        upsertJsonLd(seo.jsonLd);
    }, [seo]);

    return null;
}
