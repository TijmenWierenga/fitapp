@use('App\Support\Workout\TimeConverter')
@use('App\Support\Workout\DistanceConverter')

@props(['workout'])

@php
    $metrics = collect([
        $workout->total_duration ? ['icon' => 'clock', 'label' => 'Duration', 'value' => TimeConverter::format($workout->total_duration)] : null,
        $workout->total_distance ? ['icon' => 'map-pin', 'label' => 'Distance', 'value' => DistanceConverter::format((int) $workout->total_distance)] : null,
        $workout->total_calories ? ['icon' => 'fire', 'label' => 'Calories', 'value' => $workout->total_calories . ' kcal'] : null,
        $workout->avg_heart_rate ? ['icon' => 'heart', 'label' => 'Avg HR', 'value' => $workout->avg_heart_rate . ' bpm'] : null,
        $workout->max_heart_rate ? ['icon' => 'heart', 'label' => 'Max HR', 'value' => $workout->max_heart_rate . ' bpm'] : null,
    ])->filter()->values();
@endphp

@if($metrics->isNotEmpty())
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
        @foreach($metrics as $metric)
            <div class="flex items-center gap-2.5 rounded-lg bg-zinc-100 dark:bg-zinc-800 px-3 py-2.5">
                <flux:icon :name="$metric['icon']" class="size-4 text-zinc-400 dark:text-zinc-500 shrink-0" />
                <div class="min-w-0">
                    <div class="text-[10px] font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wide">{{ $metric['label'] }}</div>
                    <div class="text-sm font-semibold text-zinc-900 dark:text-white truncate">{{ $metric['value'] }}</div>
                </div>
            </div>
        @endforeach
    </div>
@endif
