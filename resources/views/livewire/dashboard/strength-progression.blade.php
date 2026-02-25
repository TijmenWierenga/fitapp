<div class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 overflow-hidden">
    <div class="pt-5 px-6 pb-3">
        <flux:heading size="lg" class="flex items-center gap-2">
            Strength Progression
            <flux:tooltip toggleable>
                <flux:button icon="information-circle" size="sm" variant="ghost" />
                <flux:tooltip.content class="max-w-[18rem]">
                    Estimated 1RM progression using the Epley formula. Compares the last 4 weeks to the previous 4 weeks.
                </flux:tooltip.content>
            </flux:tooltip>
        </flux:heading>
        <flux:text class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
            Your estimated one-rep max changes over time.
        </flux:text>
    </div>

    @php
        $summary = $this->workloadSummary;
        $progressions = collect($summary->strengthProgression)
            ->sortByDesc(fn ($p) => abs($p->changePct ?? 0));
    @endphp

    @if($progressions->isEmpty())
        <div class="p-6">
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                No strength progression data yet. Complete workouts with weighted strength exercises to track your e1RM progress.
            </flux:text>
        </div>
    @else
        <div class="px-6 pb-4">
            {{-- Header --}}
            <div class="flex items-center border-y border-zinc-200 dark:border-zinc-700 py-2">
                <div class="flex-1 text-[11px] font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Exercise</div>
                <div class="w-[120px] text-right text-[11px] font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Current e1RM</div>
                <div class="w-[120px] text-right text-[11px] font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Previous e1RM</div>
                <div class="w-[80px] text-right text-[11px] font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Change</div>
            </div>

            {{-- Rows --}}
            @foreach($progressions as $progression)
                @php
                    $changeColor = match (true) {
                        $progression->changePct === null => 'text-zinc-400 dark:text-zinc-500',
                        $progression->changePct > 0 => 'text-green-600 dark:text-green-400',
                        $progression->changePct < 0 => 'text-red-600 dark:text-red-400',
                        default => 'text-zinc-500 dark:text-zinc-400',
                    };
                    $changePrefix = $progression->changePct !== null && $progression->changePct > 0 ? '+' : '';
                    $changeIcon = match (true) {
                        $progression->changePct === null => null,
                        $progression->changePct > 0 => 'arrow-trending-up',
                        $progression->changePct < 0 => 'arrow-trending-down',
                        default => null,
                    };
                    $isLast = $loop->last;
                @endphp
                <div @class(['flex items-center py-2.5', 'border-b border-zinc-100 dark:border-zinc-800' => ! $isLast])>
                    <div class="flex-1 text-xs text-zinc-900 dark:text-zinc-100 truncate pr-2">{{ $progression->exerciseName }}</div>
                    <div class="w-[120px] text-right text-xs tabular-nums text-zinc-500 dark:text-zinc-400">{{ number_format($progression->currentE1RM, 1) }} kg</div>
                    <div class="w-[120px] text-right text-xs tabular-nums text-zinc-500 dark:text-zinc-400">
                        @if($progression->previousE1RM !== null)
                            {{ number_format($progression->previousE1RM, 1) }} kg
                        @else
                            <span class="text-zinc-400 dark:text-zinc-500">&mdash;</span>
                        @endif
                    </div>
                    <div class="w-[80px] text-right text-xs tabular-nums {{ $changeColor }}">
                        @if($progression->changePct !== null)
                            <span class="inline-flex items-center justify-end gap-0.5">
                                @if($changeIcon)
                                    <flux:icon :icon="$changeIcon" class="size-3.5" />
                                @endif
                                <span class="font-semibold">{{ $changePrefix }}{{ number_format($progression->changePct, 1) }}%</span>
                            </span>
                        @else
                            <span class="text-zinc-400 dark:text-zinc-500">&mdash;</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
