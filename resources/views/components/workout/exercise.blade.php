@use('App\Models\StrengthExercise')
@use('App\Models\CardioExercise')
@use('App\Models\DurationExercise')
@use('App\Support\Workout\WorkoutDisplayFormatter as Format')

@props(['exercise'])

@php
    $exerciseable = $exercise->exerciseable;

    $dotColor = match(true) {
        $exerciseable instanceof StrengthExercise => 'bg-orange-400',
        $exerciseable instanceof CardioExercise => 'bg-blue-400',
        $exerciseable instanceof DurationExercise => 'bg-emerald-400',
        default => 'bg-zinc-400',
    };

    $typeLabel = match(true) {
        $exerciseable instanceof StrengthExercise => 'Strength',
        $exerciseable instanceof CardioExercise => 'Cardio',
        $exerciseable instanceof DurationExercise => 'Duration',
        default => 'Exercise',
    };

    // Build descriptive lines grouped by category
    $whatLines = [];
    $effortLines = [];
    $restLines = [];

    if ($exerciseable instanceof StrengthExercise) {
        $setsReps = Format::setsReps($exerciseable->target_sets, $exerciseable->target_reps_min, $exerciseable->target_reps_max);
        if ($setsReps) {
            $whatLines[] = $setsReps;
        }

        $weight = Format::weight($exerciseable->target_weight);
        if ($weight) {
            $whatLines[] = "at {$weight}";
        }

        if ($exerciseable->target_tempo) {
            $whatLines[] = "tempo {$exerciseable->target_tempo}";
        }

        $rpe = Format::rpe($exerciseable->target_rpe);
        if ($rpe) {
            $effortLines[] = $rpe;
        }

        $rest = Format::rest($exerciseable->rest_after);
        if ($rest) {
            $restLines[] = "{$rest} between sets";
        }
    } elseif ($exerciseable instanceof CardioExercise) {
        $duration = Format::duration($exerciseable->target_duration);
        if ($duration) {
            $whatLines[] = $duration;
        }

        $distance = Format::distance($exerciseable->target_distance);
        if ($distance) {
            $whatLines[] = $distance;
        }

        $pace = Format::paceRange($exerciseable->target_pace_min, $exerciseable->target_pace_max);
        if ($pace) {
            $effortLines[] = "Pace: {$pace}";
        }

        $hrZone = Format::hrZone($exerciseable->target_heart_rate_zone);
        if ($hrZone) {
            $effortLines[] = $hrZone;
        }

        $hrRange = Format::hrRange($exerciseable->target_heart_rate_min, $exerciseable->target_heart_rate_max);
        if ($hrRange) {
            $effortLines[] = $hrRange;
        }

        $power = Format::power($exerciseable->target_power);
        if ($power) {
            $effortLines[] = $power;
        }
    } elseif ($exerciseable instanceof DurationExercise) {
        $duration = Format::duration($exerciseable->target_duration);
        if ($duration) {
            $whatLines[] = $duration;
        }

        $rpe = Format::rpe($exerciseable->target_rpe);
        if ($rpe) {
            $effortLines[] = $rpe;
        }
    }
@endphp

<div class="rounded-lg bg-zinc-50 dark:bg-zinc-800/50 p-3">
    {{-- Exercise name with type indicator --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="size-2.5 rounded-full {{ $dotColor }} shrink-0"></span>
            <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ $exercise->name }}</span>
        </div>
        <span class="text-xs text-zinc-400 dark:text-zinc-500">{{ $typeLabel }}</span>
    </div>

    {{-- Detail rows --}}
    <div class="mt-2 ml-4.5 space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
        @if(!empty($whatLines))
            <div class="flex gap-2">
                <span class="shrink-0 font-medium text-zinc-500 dark:text-zinc-400 w-12">Do</span>
                <span>{{ implode(', ', $whatLines) }}</span>
            </div>
        @endif

        @if(!empty($effortLines))
            <div class="flex gap-2">
                <span class="shrink-0 font-medium text-zinc-500 dark:text-zinc-400 w-12">Effort</span>
                <span>{{ implode(', ', $effortLines) }}</span>
            </div>
        @endif

        @if(!empty($restLines))
            <div class="flex gap-2">
                <span class="shrink-0 font-medium text-zinc-500 dark:text-zinc-400 w-12">Rest</span>
                <span>{{ implode(', ', $restLines) }}</span>
            </div>
        @endif

        @if($exercise->notes)
            <div class="flex gap-2">
                <span class="shrink-0 font-medium text-zinc-500 dark:text-zinc-400 w-12">Note</span>
                <span class="italic">{{ $exercise->notes }}</span>
            </div>
        @endif
    </div>
</div>
