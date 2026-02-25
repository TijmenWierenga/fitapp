@use('App\Domain\Workload\Calculators\DurationEstimator')
@use('App\Domain\Workload\PlannedBlockMapper')

@props(['section'])

@php
    $estimatedSeconds = (new DurationEstimator)->estimate(PlannedBlockMapper::fromSection($section));
    $estimatedMinutes = $estimatedSeconds ? (int) round($estimatedSeconds / 60) : null;
@endphp

<div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 overflow-hidden">
    {{-- Section header --}}
    @if($section->name)
        <div class="flex items-center justify-between px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center gap-2.5">
                <flux:heading size="sm" class="font-semibold">{{ $section->name }}</flux:heading>
            </div>
            @if($estimatedMinutes)
                <div class="rounded bg-zinc-200 dark:bg-zinc-900 px-2 py-0.5">
                    <span class="text-[10px] font-medium text-zinc-500">~{{ $estimatedMinutes }} min</span>
                </div>
            @endif
        </div>
    @endif

    {{-- Section body --}}
    <div class="p-4 space-y-3">
        @foreach($section->blocks as $block)
            <x-workout.block :block="$block" />
        @endforeach

        {{-- Section note --}}
        @if($section->notes)
            <div class="rounded bg-accent/5 dark:bg-accent/10 p-2 flex items-start gap-1.5">
                <flux:icon.chat-bubble-left class="size-3 text-accent/60 shrink-0 mt-0.5" />
                <span class="text-[11px] text-zinc-500 dark:text-zinc-400 leading-snug">{{ $section->notes }}</span>
            </div>
        @endif
    </div>
</div>
