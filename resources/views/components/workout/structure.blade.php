@props(['sections'])

<div class="space-y-4">
    @foreach($sections as $section)
        <x-workout.section :section="$section" />
    @endforeach
</div>
