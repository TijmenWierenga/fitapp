<div class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
    <div class="mb-4">
        <flux:heading size="lg" class="flex items-center gap-2">
            Muscle Group Volume
            <flux:tooltip toggleable>
                <flux:button icon="information-circle" size="sm" variant="ghost" />
                <flux:tooltip.content class="max-w-[18rem]">
                    Weekly set counts per muscle group from strength exercises, weighted by load factor.
                </flux:tooltip.content>
            </flux:tooltip>
        </flux:heading>
        <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            Your weekly training volume per muscle group.
        </flux:text>
    </div>

    @php
        $summary = $this->workloadSummary;
        $volumes = $summary->muscleGroupVolume;
        $maxSets = $volumes->max('currentWeekSets') ?: 1;
        $injuredBodyParts = $summary->activeInjuries->pluck('body_part')->toArray();
    @endphp

    @if($volumes->isEmpty())
        <flux:text class="text-zinc-500 dark:text-zinc-400">
            No volume data yet. Complete workouts with linked strength exercises to see your muscle group volume.
        </flux:text>
    @else
        <div class="space-y-3">
            @foreach($volumes->sortByDesc('currentWeekSets') as $volume)
                @php
                    $barWidth = ($volume->currentWeekSets / $maxSets) * 100;
                    $avgMarker = $maxSets > 0 ? ($volume->fourWeekAverageSets / $maxSets) * 100 : 0;
                    $trendIcon = match ($volume->trend->value) {
                        'increasing' => 'arrow-up',
                        'decreasing' => 'arrow-down',
                        default => 'minus',
                    };
                    $trendColor = match ($volume->trend->value) {
                        'increasing' => 'text-green-600 dark:text-green-400',
                        'decreasing' => 'text-red-600 dark:text-red-400',
                        default => 'text-zinc-400 dark:text-zinc-500',
                    };
                    $barColor = match ($volume->trend->value) {
                        'increasing' => 'bg-green-500 dark:bg-green-400',
                        'decreasing' => 'bg-red-400 dark:bg-red-500',
                        default => 'bg-blue-500 dark:bg-blue-400',
                    };
                    $hasInjury = in_array($volume->bodyPart, $injuredBodyParts);
                @endphp
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center gap-2">
                            <flux:text class="text-sm font-medium min-w-[100px]">{{ $volume->label }}</flux:text>
                            <flux:icon :icon="$trendIcon" class="size-3.5 {{ $trendColor }}" />
                            @if($hasInjury)
                                <flux:badge size="sm" variant="danger" icon="exclamation-triangle">Injured</flux:badge>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:tooltip>
                                <flux:text class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400 cursor-help">
                                    {{ number_format($volume->currentWeekSets, 1) }} sets
                                </flux:text>
                                <flux:tooltip.content class="max-w-[14rem]">
                                    This week: {{ number_format($volume->currentWeekSets, 1) }} sets.
                                    4-week avg: {{ number_format($volume->fourWeekAverageSets, 1) }} sets.
                                    Trend: {{ $volume->trend->value }}.
                                </flux:tooltip.content>
                            </flux:tooltip>
                        </div>
                    </div>
                    <div class="relative h-2 rounded-full bg-zinc-100 dark:bg-zinc-700 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-300 {{ $barColor }}"
                             style="width: {{ max($barWidth, 2) }}%"></div>
                        @if($avgMarker > 0 && $avgMarker <= 100)
                            <div class="absolute top-0 h-full w-0.5 bg-zinc-900 dark:bg-white"
                                 style="left: {{ min($avgMarker, 100) }}%"></div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-3 text-xs text-zinc-500 dark:text-zinc-400">
            <div class="flex items-center gap-1">
                <flux:icon icon="arrow-up" class="size-3 text-green-600 dark:text-green-400" /> Increasing
            </div>
            <div class="flex items-center gap-1">
                <flux:icon icon="minus" class="size-3 text-zinc-400" /> Stable
            </div>
            <div class="flex items-center gap-1">
                <flux:icon icon="arrow-down" class="size-3 text-red-600 dark:text-red-400" /> Decreasing
            </div>
            <div class="flex items-center gap-1">
                <span class="inline-block w-0.5 h-3 bg-zinc-900 dark:bg-white"></span> 4-week avg
            </div>
        </div>
    @endif

    @if($summary->unlinkedExerciseCount > 0)
        <flux:text class="text-xs text-zinc-400 dark:text-zinc-500 mt-3">
            {{ $summary->unlinkedExerciseCount }} {{ Str::plural('exercise', $summary->unlinkedExerciseCount) }} not linked to the exercise library and excluded from workload tracking.
        </flux:text>
    @endif
</div>
