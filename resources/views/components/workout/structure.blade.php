@props(['sections'])

<flux:card>
    <flux:heading size="lg" class="mb-4">Workout Structure</flux:heading>

    <div class="space-y-0">
        @foreach($sections as $section)
            @if(!$loop->first)
                <flux:separator class="my-4" />
            @endif

            <x-workout.section :section="$section" />
        @endforeach
    </div>
</flux:card>
