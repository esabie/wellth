<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Inertia\Support\Header;
use Symfony\Component\HttpFoundation\Response;

class ShopThrottleResponse
{
    /**
     * @param  array<string, string>  $headers
     */
    public static function make(Request $request, array $headers): Response
    {
        $seconds = max(1, (int) ($headers['Retry-After'] ?? 60));
        $message = "Too many attempts. Please wait {$seconds} seconds and try again.";

        if ($request->headers->has(Header::INERTIA)) {
            $request->session()->flash('error', $message);

            return response('', Response::HTTP_CONFLICT, [
                Header::REDIRECT => url()->previous(),
            ]);
        }

        return redirect()
            ->back()
            ->with('error', $message)
            ->withHeaders($headers);
    }
}
