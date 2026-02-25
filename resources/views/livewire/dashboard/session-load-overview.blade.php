<div class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
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
        <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
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
            <div class="rounded-xl bg-zinc-50 dark:bg-zinc-800 p-4">
                <flux:text class="text-[10px] font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-500 mb-1">Weekly sRPE</flux:text>
                <div class="text-2xl font-bold tabular-nums text-zinc-900 dark:text-white">{{ number_format($sessionLoad->currentWeeklyTotal) }}</div>
            </div>
            <div class="rounded-xl bg-zinc-50 dark:bg-zinc-800 p-4">
                <flux:text class="text-[10px] font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-500 mb-1">Sessions</flux:text>
                <div class="text-2xl font-bold tabular-nums text-zinc-900 dark:text-white">{{ $sessionLoad->currentSessionCount }}</div>
            </div>
            <div class="rounded-xl bg-zinc-50 dark:bg-zinc-800 p-4">
                <flux:tooltip content="Training variation — values above 2.0 suggest insufficient variation in session intensity.">
                    <div class="cursor-help">
                        <flux:text class="text-[10px] font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-500 mb-1">Monotony</flux:text>
                        <div class="text-2xl font-bold tabular-nums {{ $sessionLoad->monotonyWarning ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-900 dark:text-white' }}">
                            {{ number_format($sessionLoad->monotony, 1) }}
                        </div>
                    </div>
                </flux:tooltip>
            </div>
            <div class="rounded-xl bg-zinc-50 dark:bg-zinc-800 p-4">
                <flux:tooltip content="Overall training stress — weekly load multiplied by monotony.">
                    <div class="cursor-help">
                        <flux:text class="text-[10px] font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-500 mb-1">Strain</flux:text>
                        <div class="text-2xl font-bold tabular-nums text-zinc-900 dark:text-white">{{ number_format($sessionLoad->strain, 0) }}</div>
                    </div>
                </flux:tooltip>
            </div>
        </div>

        @if(count($this->chartData) > 1)
            <flux:chart :value="$this->chartData" class="h-48">
                <flux:chart.svg>
                    <flux:chart.line field="load" class="text-accent" />
                    <flux:chart.area field="load" class="text-accent/20 dark:text-accent/10" />
                    <flux:chart.point field="load" class="text-accent" />
                    <flux:chart.axis axis="x" field="week">
                        <flux:chart.axis.tick />
                        <flux:chart.axis.line />
                    </flux:chart.axis>
                    <flux:chart.axis axis="y">
                        <flux:chart.axis.grid />
                        <flux:chart.axis.tick />
                    </flux:chart.axis>
                    <flux:chart.cursor class="text-zinc-300 dark:text-zinc-600" />
                </flux:chart.svg>
                <flux:chart.tooltip>
                    <flux:chart.tooltip.heading field="week" />
                    <flux:chart.tooltip.value field="load" label="sRPE" />
                    <flux:chart.tooltip.value field="sessions" label="Sessions" />
                </flux:chart.tooltip>
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
</div>
