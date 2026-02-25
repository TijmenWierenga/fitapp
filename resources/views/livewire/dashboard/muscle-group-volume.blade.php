<div class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 overflow-hidden">
    <div class="pt-5 px-6 pb-3">
        <flux:heading size="lg" class="flex items-center gap-2">
            Muscle Group Volume
            <flux:tooltip toggleable>
                <flux:button icon="information-circle" size="sm" variant="ghost" />
                <flux:tooltip.content class="max-w-[18rem]">
                    Weekly set counts per muscle group from strength exercises, weighted by load factor.
                </flux:tooltip.content>
            </flux:tooltip>
        </flux:heading>
        <flux:text class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
            Your weekly training volume per muscle group.
        </flux:text>
    </div>

    @php
        $summary = $this->workloadSummary;
        $volumes = $summary->muscleGroupVolume->sortByDesc('currentWeekSets');
        $injuredBodyParts = $summary->activeInjuries->pluck('body_part')->toArray();
    @endphp

    @if($volumes->isEmpty())
        <div class="p-6">
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                No volume data yet. Complete workouts with linked strength exercises to see your muscle group volume.
            </flux:text>
        </div>
    @else
        <div class="px-6 pb-4">
            {{-- Header --}}
            <div class="flex items-center border-y border-zinc-200 dark:border-zinc-700 py-2">
                <div class="flex-1 text-[11px] font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Muscle Group</div>
                <div class="w-[100px] text-right text-[11px] font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Weekly Sets</div>
                <div class="w-[100px] text-right text-[11px] font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">4-Wk Avg</div>
                <div class="w-[100px] text-right text-[11px] font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Trend</div>
            </div>

            {{-- Rows --}}
            @foreach($volumes as $volume)
                @php
                    $trendIcon = match ($volume->trend) {
                        \App\Domain\Workload\Enums\Trend::Increasing => 'arrow-trending-up',
                        \App\Domain\Workload\Enums\Trend::Decreasing => 'arrow-trending-down',
                        \App\Domain\Workload\Enums\Trend::Stable => 'minus',
                    };
                    $trendColor = match ($volume->trend) {
                        \App\Domain\Workload\Enums\Trend::Increasing => 'text-green-600 dark:text-green-400',
                        \App\Domain\Workload\Enums\Trend::Decreasing => 'text-red-600 dark:text-red-400',
                        \App\Domain\Workload\Enums\Trend::Stable => 'text-zinc-400 dark:text-zinc-500',
                    };
                    $trendLabel = match ($volume->trend) {
                        \App\Domain\Workload\Enums\Trend::Increasing => 'Increasing',
                        \App\Domain\Workload\Enums\Trend::Decreasing => 'Decreasing',
                        \App\Domain\Workload\Enums\Trend::Stable => 'Stable',
                    };
                    $hasInjury = in_array($volume->bodyPart, $injuredBodyParts);
                    $isLast = $loop->last;
                @endphp
                <div @class(['flex items-center py-2.5', 'border-b border-zinc-100 dark:border-zinc-800' => ! $isLast])>
                    <div class="flex-1 flex items-center gap-2 text-xs text-zinc-900 dark:text-zinc-100 truncate pr-2">
                        {{ $volume->label }}
                        @if($hasInjury)
                            <flux:badge size="sm" variant="danger" icon="exclamation-triangle">Injured</flux:badge>
                        @endif
                    </div>
                    <div class="w-[100px] text-right text-xs tabular-nums text-zinc-500 dark:text-zinc-400">{{ number_format($volume->currentWeekSets, 1) }}</div>
                    <div class="w-[100px] text-right text-xs tabular-nums text-zinc-500 dark:text-zinc-400">{{ number_format($volume->fourWeekAverageSets, 1) }}</div>
                    <div class="w-[100px] text-right text-xs {{ $trendColor }}">
                        <span class="inline-flex items-center justify-end gap-0.5">
                            <flux:icon :icon="$trendIcon" class="size-3.5" />
                            <span class="font-semibold">{{ $trendLabel }}</span>
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if($summary->unlinkedExerciseCount > 0)
        <div class="px-6 pb-4">
            <flux:text class="text-xs text-zinc-400 dark:text-zinc-500">
                {{ $summary->unlinkedExerciseCount }} {{ Str::plural('exercise', $summary->unlinkedExerciseCount) }} not linked to the exercise library and excluded from workload tracking.
            </flux:text>
        </div>
    @endif
</div>
