@props(['section'])

<div>
    @if($section->name)
        <flux:heading size="base" class="mb-2">{{ $section->name }}</flux:heading>
    @endif

    @if($section->notes)
        <flux:text class="mb-3 text-sm italic">{{ $section->notes }}</flux:text>
    @endif

    <div class="space-y-5">
        @foreach($section->blocks as $block)
            <x-workout.block :block="$block" />
        @endforeach
    </div>
</div>
