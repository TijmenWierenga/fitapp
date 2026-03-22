<div class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
    <div class="mb-4">
        <flux:heading size="lg" class="flex items-center gap-2">
            Session Load
            <flux:tooltip toggleable>
                <flux:button icon="information-circle" size="sm" variant="ghost" />
                <flux:tooltip.content class="max-w-[18rem]">
                    Training load tracked using EWMA (Exponentially Weighted Moving Average). ACWR compares recent (acute) to long-term (chronic) load to guide safe training progression.
                </flux:tooltip.content>
            </flux:tooltip>
        </flux:heading>
        <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            Your training load trends based on session duration and effort.
        </flux:text>
    </div>

    @php
        $summary = $this->workloadSummary;
        $sessionLoad = $summary->sessionLoad;
        $ewma = $summary->ewmaLoad;
    @endphp

    @if($sessionLoad === null && $ewma === null)
        <flux:text class="text-zinc-500 dark:text-zinc-400">
            No session load data yet. Complete workouts with duration to see your training load trends.
        </flux:text>
    @else
        <div class="grid grid-cols-2 gap-3 mb-4">
            @if($ewma !== null)
                <div class="rounded-xl bg-zinc-50 dark:bg-zinc-800 p-4">
                    <flux:tooltip content="Acute:Chronic Workload Ratio — compares recent training load to long-term average.">
                        <div class="cursor-help">
                            <flux:text class="text-[10px] font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-500 mb-1">ACWR</flux:text>
                            <div class="flex items-center gap-2">
                                <span class="text-2xl font-bold tabular-nums {{ $ewma->acwrZone->color() }}">
                                    {{ $ewma->acwr !== null ? number_format($ewma->acwr, 2) : '—' }}
                                </span>
                                <flux:badge color="{{ $ewma->acwrZone->badgeColor() }}" size="sm">{{ $ewma->acwrZone->label() }}</flux:badge>
                            </div>
                        </div>
                    </flux:tooltip>
                </div>
                <div class="rounded-xl bg-zinc-50 dark:bg-zinc-800 p-4">
                    <flux:tooltip content="Training Stress Balance — positive means fresh, negative means fatigued.">
                        <div class="cursor-help">
                            <flux:text class="text-[10px] font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-500 mb-1">Freshness</flux:text>
                            <div class="text-2xl font-bold tabular-nums {{ $ewma->tsb >= 0 ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400' }}">
                                {{ ($ewma->tsb >= 0 ? '+' : '') . number_format($ewma->tsb, 0) }}
                            </div>
                        </div>
                    </flux:tooltip>
                </div>
            @endif

            @if($sessionLoad !== null)
                <div class="rounded-xl bg-zinc-50 dark:bg-zinc-800 p-4">
                    <flux:text class="text-[10px] font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-500 mb-1">Weekly sRPE</flux:text>
                    <div class="text-2xl font-bold tabular-nums text-zinc-900 dark:text-white">{{ number_format($sessionLoad->currentWeeklyTotal) }}</div>
                </div>
                <div class="rounded-xl bg-zinc-50 dark:bg-zinc-800 p-4">
                    <flux:text class="text-[10px] font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-500 mb-1">Sessions</flux:text>
                    <div class="text-2xl font-bold tabular-nums text-zinc-900 dark:text-white">{{ $sessionLoad->currentSessionCount }}</div>
                </div>
            @endif
        </div>

        @if(count($this->ewmaChartData) > 1)
            <flux:chart :value="$this->ewmaChartData" class="h-48">
                <flux:chart.svg>
                    <flux:chart.line field="acute" class="text-rose-500" />
                    <flux:chart.line field="chronic" class="text-blue-500" />
                    <flux:chart.area field="acute" class="text-rose-500/10" />
                    <flux:chart.area field="chronic" class="text-blue-500/10" />
                    <flux:chart.axis axis="x" field="date">
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
                    <flux:chart.tooltip.heading field="date" />
                    <flux:chart.tooltip.value field="acute" label="Acute" />
                    <flux:chart.tooltip.value field="chronic" label="Chronic" />
                </flux:chart.tooltip>
            </flux:chart>

            <div class="flex items-center gap-4 mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                <span class="flex items-center gap-1"><span class="inline-block w-3 h-0.5 rounded bg-rose-500"></span> Acute (7-day)</span>
                <span class="flex items-center gap-1"><span class="inline-block w-3 h-0.5 rounded bg-blue-500"></span> Chronic (28-day)</span>
            </div>
        @endif

        @if($ewma !== null && in_array($ewma->acwrZone, [\App\Domain\Workload\Enums\AcwrZone::Caution, \App\Domain\Workload\Enums\AcwrZone::Danger]))
            <flux:callout variant="{{ $ewma->acwrZone === \App\Domain\Workload\Enums\AcwrZone::Danger ? 'danger' : 'warning' }}" class="mt-4" icon="exclamation-triangle">
                {{ $ewma->acwrZone->description() }}
            </flux:callout>
        @endif

        @if($sessionLoad !== null && ($sessionLoad->monotonyWarning || $sessionLoad->weekOverWeekWarning))
            <flux:accordion class="mt-4">
                <flux:accordion.item>
                    <flux:accordion.heading>
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">Advanced details</span>
                    </flux:accordion.heading>
                    <flux:accordion.content>
                        <div class="grid grid-cols-2 gap-3 mb-3">
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

                        @if($sessionLoad->weekOverWeekWarning)
                            @php
                                $change = $sessionLoad->weekOverWeekChangePct;
                                $direction = $change > 0 ? 'increased' : 'decreased';
                            @endphp
                            <flux:callout variant="warning" icon="exclamation-triangle">
                                Load {{ $direction }} by {{ number_format(abs($change), 1) }}% week-over-week (threshold: 15%).
                            </flux:callout>
                        @endif

                        @if($sessionLoad->monotonyWarning)
                            <flux:callout variant="warning" class="mt-2" icon="exclamation-triangle">
                                Training monotony ({{ number_format($sessionLoad->monotony, 1) }}) is high. Consider varying session intensity.
                            </flux:callout>
                        @endif
                    </flux:accordion.content>
                </flux:accordion.item>
            </flux:accordion>
        @endif
    @endif
</div>
