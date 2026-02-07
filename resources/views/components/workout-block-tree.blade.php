@props(['blocks', 'compact' => false])

@if($blocks->isNotEmpty())
    <div class="space-y-1">
        @foreach($blocks as $block)
            <x-workout-block :block="$block" :depth="1" :compact="$compact" />
        @endforeach
    </div>
@else
    <x-empty-state icon="document" message="No workout structure defined" />
@endif
