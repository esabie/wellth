<?php

namespace App\Http\Middleware;

use App\Support\VisitorCountry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockRestrictedCountries
{
    public function __construct(private VisitorCountry $countries) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var list<string> $blocked */
        $blocked = array_values(array_filter(array_map(
            static fn (mixed $code): string => strtoupper(trim((string) $code)),
            config('shop.blocked_countries', []),
        )));

        if ($blocked === []) {
            return $next($request);
        }

        $country = $this->countries->code($request);

        if ($country !== null && in_array($country, $blocked, true)) {
            abort(Response::HTTP_FORBIDDEN, 'Access from your region is not available.');
        }

        return $next($request);
    }
}
