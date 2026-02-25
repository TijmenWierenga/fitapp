<div class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 overflow-hidden">
    <div class="p-6 pb-0">
        <div class="mb-4">
            <flux:heading size="lg" class="flex items-center gap-2">
                Strength Progression
                <flux:tooltip toggleable>
                    <flux:button icon="information-circle" size="sm" variant="ghost" />
                    <flux:tooltip.content class="max-w-[18rem]">
                        Estimated 1RM progression using the Epley formula. Compares the last 4 weeks to the previous 4 weeks.
                    </flux:tooltip.content>
                </flux:tooltip>
            </flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Your estimated one-rep max changes over time.
            </flux:text>
        </div>
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
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Exercise</flux:table.column>
                <flux:table.column class="text-right">Current e1RM</flux:table.column>
                <flux:table.column class="text-right">Previous e1RM</flux:table.column>
                <flux:table.column class="text-right">Change</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
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
                            $progression->changePct > 0 => 'arrow-up',
                            $progression->changePct < 0 => 'arrow-down',
                            default => null,
                        };
                    @endphp
                    <flux:table.row>
                        <flux:table.cell class="font-medium">{{ $progression->exerciseName }}</flux:table.cell>
                        <flux:table.cell class="text-right tabular-nums">{{ number_format($progression->currentE1RM, 1) }} kg</flux:table.cell>
                        <flux:table.cell class="text-right tabular-nums">
                            @if($progression->previousE1RM !== null)
                                {{ number_format($progression->previousE1RM, 1) }} kg
                            @else
                                <span class="text-zinc-400 dark:text-zinc-500">&mdash;</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-right tabular-nums {{ $changeColor }}">
                            @if($progression->changePct !== null)
                                <span class="inline-flex items-center gap-1">
                                    @if($changeIcon)
                                        <flux:icon :icon="$changeIcon" class="size-4" />
                                    @endif
                                    <span>{{ $changePrefix }}{{ number_format($progression->changePct, 1) }}%</span>
                                </span>
                            @else
                                <span class="text-zinc-400 dark:text-zinc-500">&mdash;</span>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
