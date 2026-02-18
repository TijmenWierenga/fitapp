<flux:card>
    <div class="mb-4">
        <flux:heading size="lg" class="flex items-center gap-2">
            Session Load
            <flux:tooltip toggleable>
                <flux:button icon="information-circle" size="sm" variant="ghost" />
                <flux:tooltip.content class="max-w-[18rem]">
                    Session load (sRPE) is duration x RPE for each workout. Higher values mean more training stress.
                </flux:tooltip.content>
            </flux:tooltip>
        </flux:heading>
        <flux:text class="mt-1 text-sm">
            Your weekly training load based on session duration and effort.
        </flux:text>
    </div>

    @php
        $summary = $this->workloadSummary;
        $sessionLoad = $summary->sessionLoad;
    @endphp

    @if($sessionLoad === null)
        <flux:text class="text-zinc-500 dark:text-zinc-400">
            No session load data yet. Complete workouts with duration to see your training load trends.
        </flux:text>
    @else
        <div class="grid grid-cols-2 gap-3 mb-4">
            <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800 p-3">
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">Weekly sRPE</flux:text>
                <div class="text-lg font-semibold tabular-nums">{{ number_format($sessionLoad->currentWeeklyTotal) }}</div>
            </div>
            <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800 p-3">
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">Sessions</flux:text>
                <div class="text-lg font-semibold tabular-nums">{{ $sessionLoad->currentSessionCount }}</div>
            </div>
            <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800 p-3">
                <flux:tooltip content="Training variation — values above 2.0 suggest insufficient variation in session intensity.">
                    <div class="cursor-help">
                        <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">Monotony</flux:text>
                        <div class="text-lg font-semibold tabular-nums {{ $sessionLoad->monotonyWarning ? 'text-amber-600 dark:text-amber-400' : '' }}">
                            {{ number_format($sessionLoad->monotony, 1) }}
                        </div>
                    </div>
                </flux:tooltip>
            </div>
            <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800 p-3">
                <flux:tooltip content="Overall training stress — weekly load multiplied by monotony.">
                    <div class="cursor-help">
                        <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">Strain</flux:text>
                        <div class="text-lg font-semibold tabular-nums">{{ number_format($sessionLoad->strain, 0) }}</div>
                    </div>
                </flux:tooltip>
            </div>
        </div>

        @if(count($this->chartData) > 1)
            <flux:chart :value="$this->chartData" class="h-24">
                <flux:chart.svg gutter="0 0 0 0">
                    <flux:chart.line field="load" class="text-blue-500 dark:text-blue-400" />
                    <flux:chart.area field="load" class="text-blue-100 dark:text-blue-400/20" />
                </flux:chart.svg>
            </flux:chart>
        @endif

        @if($sessionLoad->weekOverWeekWarning)
            @php
                $change = $sessionLoad->weekOverWeekChangePct;
                $direction = $change > 0 ? 'increased' : 'decreased';
            @endphp
            <flux:callout variant="warning" class="mt-4" icon="exclamation-triangle">
                Load {{ $direction }} by {{ number_format(abs($change), 1) }}% week-over-week (threshold: 15%).
            </flux:callout>
        @endif

        @if($sessionLoad->monotonyWarning)
            <flux:callout variant="warning" class="mt-4" icon="exclamation-triangle">
                Training monotony ({{ number_format($sessionLoad->monotony, 1) }}) is high. Consider varying session intensity.
            </flux:callout>
        @endif
    @endif
</flux:card>
