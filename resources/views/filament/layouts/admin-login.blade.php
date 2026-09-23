@props([
    'after' => null,
    'heading' => null,
    'subheading' => null,
])

@php
    use Filament\Support\Enums\Width;
    use Filament\Support\Facades\FilamentView;
    use Filament\View\PanelsRenderHook;

    $livewire ??= null;
    $renderHookScopes = $livewire?->getRenderHookScopes();
    $maxContentWidth ??= (filament()->getSimplePageMaxContentWidth() ?? Width::Large);

    if (is_string($maxContentWidth)) {
        $maxContentWidth = Width::tryFrom($maxContentWidth) ?? $maxContentWidth;
    }
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    <div class="fi-simple-layout lens-login">
        {{ FilamentView::renderHook(PanelsRenderHook::SIMPLE_LAYOUT_START, scopes: $renderHookScopes) }}

        <aside class="lens-login-stage" aria-hidden="true">
            <div class="lens-login-grid"></div>
            <div class="lens-login-glow"></div>
            <div class="lens-login-aperture">
                <span class="lens-login-ring lens-login-ring-a"></span>
                <span class="lens-login-ring lens-login-ring-b"></span>
                <span class="lens-login-ring lens-login-ring-c"></span>
                <span class="lens-login-core"></span>
            </div>
            <div class="lens-login-copy">
                <p class="lens-login-brand">Lens</p>
                <p class="lens-login-tag">The marketplace for photographers, studios, and creators across Egypt.</p>
                <ul class="lens-login-points">
                    <li>Bookings in escrow</li>
                    <li>Verified talent</li>
                    <li>Protected delivery</li>
                </ul>
            </div>
        </aside>

        <div class="fi-simple-main-ctn lens-login-panel">
            <main
                id="fi-main-content"
                tabindex="-1"
                @class([
                    'fi-simple-main',
                    ($maxContentWidth instanceof Width) ? "fi-width-{$maxContentWidth->value}" : $maxContentWidth,
                ])
            >
                {{ $slot }}
            </main>
            <p class="lens-login-foot">Staff only · English LTR · Dark console</p>
        </div>

        {{ FilamentView::renderHook(PanelsRenderHook::SIMPLE_LAYOUT_END, scopes: $renderHookScopes) }}
    </div>
</x-filament-panels::layout.base>
