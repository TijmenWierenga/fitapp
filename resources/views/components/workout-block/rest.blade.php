@use('App\Support\Workout\TimeConverter')

@props(['rest'])

<div class="flex items-center gap-2 pl-6 pb-2 text-sm text-zinc-400 dark:text-zinc-500">
    @if($rest->duration_seconds)
        <span>Rest for {{ TimeConverter::format($rest->duration_seconds) }}</span>
    @else
        <span>Rest</span>
    @endif
</div>
