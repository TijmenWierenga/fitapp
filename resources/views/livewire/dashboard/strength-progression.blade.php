<flux:card>
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
        <flux:text class="mt-1 text-sm">
            Your estimated one-rep max changes over time.
        </flux:text>
    </div>

    @php
        $summary = $this->workloadSummary;
        $progressions = collect($summary->strengthProgression)
            ->sortByDesc(fn ($p) => abs($p->changePct ?? 0));
    @endphp

    @if($progressions->isEmpty())
        <flux:text class="text-zinc-500 dark:text-zinc-400">
            No strength progression data yet. Complete workouts with weighted strength exercises to track your e1RM progress.
        </flux:text>
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
                            $progression->changePct === null => 'text-zinc-400',
                            $progression->changePct > 0 => 'text-green-600 dark:text-green-400',
                            $progression->changePct < 0 => 'text-red-600 dark:text-red-400',
                            default => 'text-zinc-500',
                        };
                        $changePrefix = $progression->changePct !== null && $progression->changePct > 0 ? '+' : '';
                    @endphp
                    <flux:table.row>
                        <flux:table.cell class="font-medium">{{ $progression->exerciseName }}</flux:table.cell>
                        <flux:table.cell class="text-right tabular-nums">{{ number_format($progression->currentE1RM, 1) }} kg</flux:table.cell>
                        <flux:table.cell class="text-right tabular-nums">
                            @if($progression->previousE1RM !== null)
                                {{ number_format($progression->previousE1RM, 1) }} kg
                            @else
                                <span class="text-zinc-400">&mdash;</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="text-right tabular-nums {{ $changeColor }}">
                            @if($progression->changePct !== null)
                                {{ $changePrefix }}{{ number_format($progression->changePct, 1) }}%
                            @else
                                <span class="text-zinc-400">&mdash;</span>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</flux:card>
