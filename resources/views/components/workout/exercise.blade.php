@props(['exercise'])

@php
    /** @var App\Models\BlockExercise $exercise */
    /** @var App\DataTransferObjects\Workout\ExercisePresentation|null $presentation */
    $presentation = $exercise->exerciseable?->present();
@endphp

<div class="rounded-lg bg-zinc-50 dark:bg-zinc-800/50 p-3">
    {{-- Exercise name with type indicator --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            @if($presentation)
                <span class="size-2.5 rounded-full {{ $presentation->dotColor }} shrink-0"></span>
            @endif
            @if($exercise->exercise_id)
                <button
                    type="button"
                    wire:click="$dispatch('show-exercise-detail', { exerciseId: {{ $exercise->exercise_id }} })"
                    class="text-sm font-semibold text-accent hover:underline text-left cursor-pointer"
                >
                    {{ $exercise->name }}
                </button>
            @else
                <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ $exercise->name }}</span>
            @endif
        </div>
        @if($presentation)
            <span class="text-xs text-zinc-400 dark:text-zinc-500">{{ $presentation->typeLabel }}</span>
        @endif
    </div>

    @if($presentation)
        {{-- Detail rows --}}
        <div class="mt-2 ml-4.5 space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
            @if(!empty($presentation->whatLines))
                <div class="flex gap-2">
                    <span class="shrink-0 font-medium text-zinc-500 dark:text-zinc-400 w-12">Do</span>
                    <span>{{ implode(', ', $presentation->whatLines) }}</span>
                </div>
            @endif

            @if(!empty($presentation->effortLines))
                <div class="flex gap-2">
                    <span class="shrink-0 font-medium text-zinc-500 dark:text-zinc-400 w-12">Effort</span>
                    <span>{{ implode(', ', $presentation->effortLines) }}</span>
                </div>
            @endif

            @if(!empty($presentation->restLines))
                <div class="flex gap-2">
                    <span class="shrink-0 font-medium text-zinc-500 dark:text-zinc-400 w-12">Rest</span>
                    <span>{{ implode(', ', $presentation->restLines) }}</span>
                </div>
            @endif

            @if($exercise->notes)
                <div class="flex gap-2">
                    <span class="shrink-0 font-medium text-zinc-500 dark:text-zinc-400 w-12">Note</span>
                    <span class="italic">{{ $exercise->notes }}</span>
                </div>
            @endif
        </div>
    @endif
</div>
