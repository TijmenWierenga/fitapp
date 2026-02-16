<x-layouts.app :title="__('Documentation')">
    <div class="max-w-3xl mx-auto">
        <div class="mb-8">
            <flux:heading size="xl">Documentation</flux:heading>
            <flux:text class="mt-2">
                Guides and references to help you get the most out of {{ config('app.name') }}.
            </flux:text>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <a href="{{ route('docs.workload-guide') }}" class="group block rounded-xl border border-zinc-200 p-5 transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700 dark:hover:border-zinc-600" wire:navigate>
                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon.chart-bar class="size-5 text-zinc-600 dark:text-zinc-400" />
                </div>
                <flux:heading size="lg">Workload Guide</flux:heading>
                <flux:text class="mt-1">
                    Understand how training load tracking works, ACWR zones, and muscle group load factors.
                </flux:text>
            </a>

            <a href="{{ route('docs.garmin-export') }}" class="group block rounded-xl border border-zinc-200 p-5 transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700 dark:hover:border-zinc-600" wire:navigate>
                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon.arrow-down-tray class="size-5 text-zinc-600 dark:text-zinc-400" />
                </div>
                <flux:heading size="lg">Garmin Export</flux:heading>
                <flux:text class="mt-1">
                    Export workouts as FIT files and transfer them to your Garmin watch.
                </flux:text>
            </a>
        </div>
    </div>
</x-layouts.app>
