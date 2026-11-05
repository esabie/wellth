<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;

class PageSeo
{
    private const FALLBACK_DESCRIPTION = 'Organic wellness shots with bold flavor and clean ingredients, made fresh in Ghana for your daily ritual.';

    public function __construct(private Faqs $faqs) {}

    /**
     * @return array{
     *     siteName: string,
     *     title: string,
     *     description: string,
     *     canonical: string,
     *     image: string,
     *     robots: string,
     *     type: string,
     *     jsonLd: array<string, mixed>|null
     * }
     */
    public function forRequest(Request $request): array
    {
        $siteName = (string) config('app.name', 'Wellth');
        $page = $this->page($request, $siteName);

        return [
            'siteName' => $siteName,
            'title' => $this->documentTitle($page['title'], $siteName),
            'description' => $page['description'],
            'canonical' => $page['canonical'] ?? url()->current(),
            'image' => $page['image'] ?? url('/images/formulas/bouquet.jpg'),
            'robots' => $page['index'] ? 'index, follow' : 'noindex, nofollow',
            'type' => $page['type'],
            'jsonLd' => $page['jsonLd'],
        ];
    }

    /**
     * @return array{
     *     title: string|null,
     *     description: string,
     *     canonical: string|null,
     *     image: string|null,
     *     index: bool,
     *     type: string,
     *     jsonLd: array<string, mixed>|null
     * }
     */
    private function page(Request $request, string $siteName): array
    {
        $page = [
            'title' => null,
            'description' => self::FALLBACK_DESCRIPTION,
            'canonical' => null,
            'image' => null,
            'index' => true,
            'type' => 'website',
            'jsonLd' => null,
        ];

        return match ($request->route()?->getName()) {
            'home' => [
                ...$page,
                'title' => 'Organic wellness shots',
                'jsonLd' => [
                    '@context' => 'https://schema.org',
                    '@graph' => [
                        [
                            '@type' => 'Organization',
                            'name' => $siteName,
                            'url' => route('home'),
                            'logo' => url('/images/logo.png'),
                        ],
                        [
                            '@type' => 'WebSite',
                            'name' => $siteName,
                            'url' => route('home'),
                        ],
                    ],
                ],
            ],
            'shop.index' => [
                ...$page,
                'title' => 'Shop',
                'description' => 'Shop Wellth organic wellness shots and juices, made fresh in Ghana for your daily ritual.',
            ],
            'faqs' => [
                ...$page,
                'title' => 'FAQs',
                'description' => 'Answers about Wellth shots, ingredients, storage, and how ordering works.',
                'jsonLd' => $this->faqSchema(),
            ],
            'shop.show' => $this->productPage($request, $siteName, $page),
            'cart.index' => [...$page, 'title' => 'Your cart', 'index' => false],
            'checkout.create' => [...$page, 'title' => 'Checkout', 'index' => false],
            'checkout.payment' => [...$page, 'title' => 'Pay by Mobile Money', 'index' => false],
            'checkout.confirmation' => [
                ...$page,
                'title' => $this->confirmationTitle($request->route('order')),
                'index' => false,
            ],
            default => $page,
        };
    }

    /**
     * @param  array{
     *     title: string|null,
     *     description: string,
     *     canonical: string|null,
     *     image: string|null,
     *     index: bool,
     *     type: string,
     *     jsonLd: array<string, mixed>|null
     * }  $page
     * @return array{
     *     title: string|null,
     *     description: string,
     *     canonical: string|null,
     *     image: string|null,
     *     index: bool,
     *     type: string,
     *     jsonLd: array<string, mixed>|null
     * }
     */
    private function productPage(Request $request, string $siteName, array $page): array
    {
        $product = $request->route('product');

        if (! $product instanceof Product) {
            return $page;
        }

        $description = $this->plainDescription($product->short_description ?: $product->description);
        $image = $this->absoluteUrl($product->imageUrl()) ?? url('/images/formulas/bouquet.jpg');
        $canonical = route('shop.show', $product);

        return [
            ...$page,
            'title' => $product->name,
            'description' => $description,
            'canonical' => $canonical,
            'image' => $image,
            'type' => 'product',
            'jsonLd' => $this->productSchema($product, $siteName, $description, $image, $canonical),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function faqSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(
                fn (array $faq): array => [
                    '@type' => 'Question',
                    'name' => $faq['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $faq['answer'],
                    ],
                ],
                $this->faqs->all(),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function productSchema(Product $product, string $siteName, string $description, string $image, string $canonical): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $description,
            'image' => $image,
            'brand' => [
                '@type' => 'Brand',
                'name' => $siteName,
            ],
            'offers' => [
                '@type' => 'Offer',
                'url' => $canonical,
                'priceCurrency' => 'GHS',
                'price' => number_format((float) $product->price, 2, '.', ''),
                'availability' => $product->isComingSoon()
                    ? 'https://schema.org/PreOrder'
                    : 'https://schema.org/InStock',
            ],
        ];
    }

    private function documentTitle(?string $title, string $siteName): string
    {
        if ($title === null || $title === '' || $title === $siteName) {
            return $siteName;
        }

        return $title.' · '.$siteName;
    }

    private function plainDescription(?string $text): string
    {
        $plain = trim(preg_replace('/\s+/', ' ', strip_tags((string) $text)) ?? '');

        if ($plain === '') {
            return self::FALLBACK_DESCRIPTION;
        }

        if (mb_strlen($plain) <= 160) {
            return $plain;
        }

        return rtrim(mb_substr($plain, 0, 157)).'…';
    }

    private function absoluteUrl(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url($path);
    }

    private function confirmationTitle(mixed $order): string
    {
        if (! $order instanceof Order) {
            return 'Order confirmed';
        }

        return match ($order->status) {
            OrderStatus::AwaitingConfirmation => 'Payment received',
            OrderStatus::Preparing => 'Order preparing',
            OrderStatus::Shipped => 'Order shipped',
            OrderStatus::Delivered => 'Order delivered',
            default => 'Order confirmed',
        };
    }
}
