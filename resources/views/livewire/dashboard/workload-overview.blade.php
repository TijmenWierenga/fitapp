<flux:card>
    <div class="mb-4">
        <flux:heading size="lg" class="flex items-center gap-2">
            Muscle Group Workload
            <flux:tooltip toggleable>
                <flux:button icon="information-circle" size="sm" variant="ghost" />
                <flux:tooltip.content class="max-w-[18rem]">
                    Shows training stress on each muscle group over the past 7 days, compared to your 4-week average.
                </flux:tooltip.content>
            </flux:tooltip>
        </flux:heading>
        <flux:text class="mt-1 text-sm">
            Your recent training load per muscle group.
            <flux:link href="{{ route('workload-guide') }}" variant="subtle" class="inline" wire:navigate>Learn how it works</flux:link>
        </flux:text>
    </div>

    @php
        $summary = $this->workloadSummary;
        $muscleGroups = $summary->muscleGroups;
        $maxLoad = $muscleGroups->max('acuteLoad') ?: 1;
        $injuredBodyParts = $summary->activeInjuries->pluck('body_part')->toArray();
    @endphp

    @if($muscleGroups->isEmpty())
        <flux:text class="text-zinc-500 dark:text-zinc-400">
            No workload data yet. Complete workouts with linked exercises to see your muscle group workload distribution.
        </flux:text>
    @else
        <div class="space-y-3">
            @foreach($muscleGroups->sortByDesc('acuteLoad') as $workload)
                @php
                    $barWidth = ($workload->acuteLoad / $maxLoad) * 100;
                    $barColorClass = match ($workload->zone->color()) {
                        'green' => 'bg-green-500 dark:bg-green-400',
                        'yellow' => 'bg-yellow-500 dark:bg-yellow-400',
                        'red' => 'bg-red-500 dark:bg-red-400',
                        default => 'bg-zinc-400 dark:bg-zinc-500',
                    };
                    $badgeColor = match ($workload->zone->color()) {
                        'green' => 'green',
                        'yellow' => 'yellow',
                        'red' => 'red',
                        default => 'zinc',
                    };
                    $hasInjuryWarning = in_array($workload->bodyPart, $injuredBodyParts)
                        && in_array($workload->zone, [\App\Enums\WorkloadZone::Caution, \App\Enums\WorkloadZone::Danger]);
                    $zoneLabel = $workload->zone->label();
                @endphp
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center gap-2">
                            <flux:text class="text-sm font-medium min-w-[100px]">{{ $workload->muscleGroupLabel }}</flux:text>
                            @if($hasInjuryWarning)
                                <flux:badge size="sm" variant="danger" icon="exclamation-triangle">Injured</flux:badge>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:tooltip toggleable content="Total volume for this muscle group in the last 7 days.">
                                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400 cursor-help">{{ number_format($workload->acuteLoad, 1) }}</flux:text>
                            </flux:tooltip>
                            @if($workload->acwr > 0)
                                <flux:tooltip toggleable>
                                    <flux:badge size="sm" variant="pill" color="{{ $badgeColor }}">{{ $workload->acwr }}</flux:badge>
                                    <flux:tooltip.content class="max-w-[16rem]">
                                        ACWR {{ $workload->acwr }} — {{ $zoneLabel }} zone. Ratio of 7-day load to 4-week weekly average.
                                    </flux:tooltip.content>
                                </flux:tooltip>
                            @endif
                        </div>
                    </div>
                    <div class="h-2 rounded-full bg-zinc-100 dark:bg-zinc-700 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-300 {{ $barColorClass }}"
                             style="width: {{ max($barWidth, 2) }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-3 text-xs text-zinc-500 dark:text-zinc-400">
            <flux:tooltip toggleable content="ACWR below 0.8 — you may be losing fitness.">
                <div class="flex items-center gap-1 cursor-help">
                    <span class="inline-block w-2 h-2 rounded-full bg-zinc-400"></span> Undertraining
                </div>
            </flux:tooltip>
            <flux:tooltip toggleable content="ACWR 0.8–1.3 — optimal training zone.">
                <div class="flex items-center gap-1 cursor-help">
                    <span class="inline-block w-2 h-2 rounded-full bg-green-500"></span> Sweet Spot
                </div>
            </flux:tooltip>
            <flux:tooltip toggleable content="ACWR 1.3–1.5 — elevated injury risk.">
                <div class="flex items-center gap-1 cursor-help">
                    <span class="inline-block w-2 h-2 rounded-full bg-yellow-500"></span> Caution
                </div>
            </flux:tooltip>
            <flux:tooltip toggleable content="ACWR above 1.5 — high injury risk, consider reducing load.">
                <div class="flex items-center gap-1 cursor-help">
                    <span class="inline-block w-2 h-2 rounded-full bg-red-500"></span> Danger
                </div>
            </flux:tooltip>
        </div>
    @endif

    @if($summary->unlinkedExerciseCount > 0)
        <flux:text class="text-xs text-zinc-400 dark:text-zinc-500 mt-3">
            {{ $summary->unlinkedExerciseCount }} {{ Str::plural('exercise', $summary->unlinkedExerciseCount) }} not linked to the exercise library and excluded from workload tracking.
        </flux:text>
    @endif
</flux:card>
