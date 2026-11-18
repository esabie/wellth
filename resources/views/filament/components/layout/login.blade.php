@props([
    'after' => null,
    'heading' => null,
    'subheading' => null,
])

@php
    use App\Support\VersionedAsset;
    use Filament\Support\Facades\FilamentView;
    use Filament\View\PanelsRenderHook;

    $livewire ??= null;
    $renderHookScopes = $livewire?->getRenderHookScopes();
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    <div class="wellth-login">
        <aside class="wellth-login-visual" aria-hidden="true">
            <img
                src="{{ VersionedAsset::url('images/hero-shots.jpg') }}"
                alt=""
                class="wellth-login-visual-image"
            />
            <div class="wellth-login-visual-scrim"></div>
            <div class="wellth-login-visual-copy">
                <p class="wellth-login-visual-brand">Wellth</p>
                <p class="wellth-login-visual-tagline">Fresh wellness shots, made in Ghana.</p>
            </div>
        </aside>

        <div class="wellth-login-form-pane">
            <main
                id="fi-main-content"
                class="wellth-login-form-main"
                tabindex="-1"
            >
                {{ $slot }}
            </main>
        </div>
    </div>

    {{ FilamentView::renderHook(PanelsRenderHook::FOOTER, scopes: $renderHookScopes) }}
</x-filament-panels::layout.base>
