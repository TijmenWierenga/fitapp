@props(['exercise'])

@php
    /** @var App\Models\BlockExercise $exercise */
    /** @var App\DataTransferObjects\Workout\ExercisePresentation|null $presentation */
    $presentation = $exercise->exerciseable?->present();
@endphp

<div class="rounded bg-zinc-100 dark:bg-zinc-950 px-2.5 py-2 space-y-1">
    {{-- Main row: dot + name + sets/reps + book icon --}}
    <div class="flex items-center gap-2">
        @if($presentation)
            <span class="size-1.5 rounded-full {{ $presentation->dotColor }} shrink-0"></span>
        @endif

        @if($exercise->exercise_id)
            <button
                type="button"
                wire:click="$dispatch('show-exercise-detail', { exerciseId: {{ $exercise->exercise_id }} })"
                class="text-xs font-medium text-accent hover:underline text-left cursor-pointer flex-1 min-w-0"
            >
                {{ $exercise->name }}
            </button>
        @else
            <span class="text-xs font-medium text-zinc-900 dark:text-zinc-100 flex-1 min-w-0">{{ $exercise->name }}</span>
        @endif

        @if($presentation && !empty($presentation->whatLines))
            <span class="text-accent font-semibold text-xs shrink-0">{{ implode(' · ', $presentation->whatLines) }}</span>
        @endif

        @if($exercise->exercise_id)
            <button
                type="button"
                wire:click="$dispatch('show-exercise-detail', { exerciseId: {{ $exercise->exercise_id }} })"
                class="text-zinc-500 hover:text-zinc-400 shrink-0"
            >
                <flux:icon.book-open class="size-3" />
            </button>
        @endif
    </div>

    {{-- Details row: effort + rest combined --}}
    @if($presentation)
        @php
            $detailParts = [];
            if (!empty($presentation->effortLines)) {
                $detailParts = array_merge($detailParts, $presentation->effortLines);
            }
            if (!empty($presentation->restLines)) {
                $detailParts = array_merge($detailParts, $presentation->restLines);
            }
        @endphp

        @if(!empty($detailParts))
            <div class="pl-3.5 text-[11px] text-zinc-500">
                {{ implode(' · ', $detailParts) }}
            </div>
        @endif
    @endif

    {{-- Note row --}}
    @if($exercise->notes)
        <div class="pl-3.5 flex items-start gap-1.5">
            <flux:icon.chat-bubble-left class="size-2.5 text-accent/70 shrink-0 mt-0.5" />
            <span class="text-[10px] text-zinc-500 italic">{{ $exercise->notes }}</span>
        </div>
    @endif
</div>
