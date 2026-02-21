<x-layouts.app.sidebar :title="$title ?? null">
    <div class="[grid-area:main] overflow-hidden min-h-0 relative" data-flux-main>
        {{ $slot }}
    </div>
</x-layouts.app.sidebar>
