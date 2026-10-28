<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title inertia>{{ config('app.name', 'Wellth') }}</title>
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
                gap: 1.1rem;
            }

            #wellth-boot-loader .boot-brand {
                margin: 0;
                font-family: Outfit, ui-sans-serif, system-ui, sans-serif;
                font-size: 1.75rem;
                font-weight: 800;
                letter-spacing: -0.04em;
                color: #1a1a1a;
            }

            #wellth-boot-loader .boot-ring {
                width: 1.65rem;
                height: 1.65rem;
                border-radius: 999px;
                border: 2px solid rgba(31, 61, 42, 0.15);
                border-top-color: #1f3d2a;
                animation: wellth-boot-spin 0.7s linear infinite;
            }

            #wellth-boot-loader .boot-bar {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 2px;
                overflow: hidden;
                background: rgba(31, 61, 42, 0.08);
            }

            #wellth-boot-loader .boot-bar span {
                display: block;
                height: 100%;
                width: 40%;
                background: #1f3d2a;
                animation: wellth-boot-bar 1s ease-in-out infinite;
            }

            @keyframes wellth-boot-spin {
                to { transform: rotate(360deg); }
            }

            @keyframes wellth-boot-bar {
                0% { transform: translateX(-120%); }
                100% { transform: translateX(320%); }
            }

            @media (prefers-reduced-motion: reduce) {
                #wellth-boot-loader .boot-ring,
                #wellth-boot-loader .boot-bar span {
                    animation: none !important;
                }

                #wellth-boot-loader .boot-ring {
                    border-top-color: #1f3d2a;
                    border-color: rgba(31, 61, 42, 0.35);
                }
            }
        </style>
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.jsx'])
        <x-inertia::head />
    </head>
    <body class="min-h-screen antialiased">
        <div id="wellth-boot-loader" aria-live="polite" aria-busy="true" aria-label="Loading Wellth">
            <div class="boot-bar" aria-hidden="true"><span></span></div>
            <div class="boot-mark">
                <p class="boot-brand">{{ config('app.name', 'Wellth') }}</p>
                <div class="boot-ring" aria-hidden="true"></div>
            </div>
        </div>

        <x-inertia::app />
    </body>
</html>
