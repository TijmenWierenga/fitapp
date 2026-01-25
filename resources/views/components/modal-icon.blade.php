@props(['icon', 'color' => 'default', 'heading' => null, 'description' => null])

@php
    $colorClasses = match($color) {
        'success' => [
            'outer' => 'border-green-100 dark:border-green-600',
            'middle' => 'border-green-200 dark:border-green-600 bg-green-100 dark:bg-green-900',
            'icon' => 'text-green-500 dark:text-green-400',
        ],
        'danger' => [
            'outer' => 'border-red-100 dark:border-red-600',
            'middle' => 'border-red-200 dark:border-red-600 bg-red-100 dark:bg-red-900',
            'icon' => 'text-red-500 dark:text-red-400',
        ],
        default => [
            'outer' => 'border-stone-100 dark:border-stone-600',
            'middle' => 'border-stone-200 dark:border-stone-600 bg-stone-100 dark:bg-stone-200',
            'icon' => 'dark:text-accent-foreground',
        ],
    };
@endphp

<div class="flex flex-col items-center space-y-4">
    <div class="p-0.5 w-auto rounded-full border {{ $colorClasses['outer'] }} bg-white dark:bg-stone-800 shadow-sm">
        <div class="p-2.5 rounded-full border {{ $colorClasses['middle'] }} overflow-hidden">
            <flux:icon :name="$icon" class="{{ $colorClasses['icon'] }}" />
        </div>
    </div>

    @if($heading || $description)
        <div class="space-y-2 text-center">
            @if($heading)
                <flux:heading size="lg">{{ $heading }}</flux:heading>
            @endif

            @if($description)
                <flux:text>{{ $description }}</flux:text>
            @endif
        </div>
    @else
        {{ $slot }}
    @endif
</div>
