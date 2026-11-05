<?php

namespace App\Http\Middleware;

use App\Support\Cart;
use App\Support\PageSeo;
use App\Support\VersionedAsset;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'app' => [
                'name' => config('app.name'),
            ],
            'seo' => fn (): array => app(PageSeo::class)->forRequest($request),
            'assets' => [
                'logo' => VersionedAsset::url('images/logo.png'),
                'logoDark' => VersionedAsset::url('images/logo-dark.png'),
            ],
            'cart' => fn (): array => app(Cart::class)->summary(),
            'flash' => [
                'success' => fn (): ?string => $request->session()->get('success'),
                'error' => fn (): ?string => $request->session()->get('error'),
                'open_cart' => fn (): bool => (bool) $request->session()->get('open_cart'),
                'payment_proof_received' => fn (): bool => (bool) $request->session()->get('payment_proof_received'),
            ],
        ];
    }
}
