<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title inertia>{{ config('app.name', 'Wellth') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
        <script>
            if ('scrollRestoration' in history) {
                history.scrollRestoration = 'manual';
            }

            if (location.pathname === '/' || location.pathname === '') {
                window.scrollTo(0, 0);
            }
        </script>
        <style>
            #wellth-boot-loader {
                position: fixed;
                inset: 0;
                z-index: 9999;
                display: grid;
                place-items: center;
                overflow: hidden;
                background: #f7f4ef;
                transition: opacity 0.4s ease, visibility 0.4s;
            }

            #wellth-boot-loader.is-done {
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
            }

            #wellth-boot-loader .boot-mark {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 1.35rem;
            }

            #wellth-boot-loader .boot-logo {
                display: block;
                width: 11.5rem;
                height: auto;
            }

            #wellth-boot-loader .boot-spinner {
                width: 1.85rem;
                height: 1.85rem;
                animation: wellth-boot-spin 1.05s linear infinite;
            }

            @keyframes wellth-boot-spin {
                to {
                    transform: rotate(360deg);
                }
            }

            @media (prefers-reduced-motion: reduce) {
                #wellth-boot-loader .boot-spinner {
                    animation: none;
                }
            }
        </style>
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.jsx'])
        <x-inertia::head />
    </head>
    <body class="min-h-screen antialiased">
        <div id="wellth-boot-loader" aria-live="polite" aria-busy="true" aria-label="Loading Wellth">
            <div class="boot-mark">
                <img src="{{ asset('images/logo.png') }}" alt="" class="boot-logo" width="184" height="161">
                <svg class="boot-spinner" viewBox="0 0 32 32" aria-hidden="true">
                    <circle cx="16" cy="16" r="13" fill="none" stroke="rgba(122, 36, 54, 0.18)" stroke-width="1.5"></circle>
                    <circle cx="16" cy="16" r="13" fill="none" stroke="#7a2436" stroke-width="1.5" stroke-linecap="round" stroke-dasharray="20 62"></circle>
                </svg>
            </div>
        </div>

        <x-inertia::app />
    </body>
</html>
