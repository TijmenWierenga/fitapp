<flux:card>
    <flux:heading size="lg" class="mb-4">Muscle Group Workload</flux:heading>

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
                    $barColorClass = match ($workload->zoneColor) {
                        'green' => 'bg-green-500 dark:bg-green-400',
                        'yellow' => 'bg-yellow-500 dark:bg-yellow-400',
                        'red' => 'bg-red-500 dark:bg-red-400',
                        default => 'bg-zinc-400 dark:bg-zinc-500',
                    };
                    $badgeVariant = match ($workload->zoneColor) {
                        'green' => 'success',
                        'yellow' => 'warning',
                        'red' => 'danger',
                        default => 'pill',
                    };
                    $hasInjuryWarning = in_array($workload->bodyPart, $injuredBodyParts)
                        && in_array($workload->zone, ['caution', 'danger']);
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
                            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ number_format($workload->acuteLoad, 1) }}</flux:text>
                            @if($workload->acwr > 0)
                                <flux:badge size="sm" variant="{{ $badgeVariant }}">{{ $workload->acwr }}</flux:badge>
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
            <div class="flex items-center gap-1">
                <span class="inline-block w-2 h-2 rounded-full bg-zinc-400"></span> Undertraining
            </div>
            <div class="flex items-center gap-1">
                <span class="inline-block w-2 h-2 rounded-full bg-green-500"></span> Sweet Spot
            </div>
            <div class="flex items-center gap-1">
                <span class="inline-block w-2 h-2 rounded-full bg-yellow-500"></span> Caution
            </div>
            <div class="flex items-center gap-1">
                <span class="inline-block w-2 h-2 rounded-full bg-red-500"></span> Danger
            </div>
        </div>
    @endif

    @if($summary->unlinkedExerciseCount > 0)
        <flux:text class="text-xs text-zinc-400 dark:text-zinc-500 mt-3">
            {{ $summary->unlinkedExerciseCount }} {{ Str::plural('exercise', $summary->unlinkedExerciseCount) }} not linked to the exercise library and excluded from workload tracking.
        </flux:text>
    @endif
</flux:card>
