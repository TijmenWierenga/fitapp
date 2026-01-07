<div class="flex justify-between items-start {{ $isChild ? 'bg-white' : 'bg-gray-50' }} rounded p-3">
    <div class="flex-1">
        <div class="flex items-center gap-2">
            <span class="font-semibold text-gray-900 capitalize">{{ $step->step_kind }}</span>
            @if($step->name)
                <span class="text-gray-600">- {{ $step->name }}</span>
            @endif
        </div>
        
        <div class="flex gap-4 mt-1 text-sm text-gray-700">
            <!-- Duration -->
            <div>
                @if($step->duration_type === 'time')
                    @php
                        $time = \App\ValueObjects\TimeValue::fromSeconds($step->duration_value);
                    @endphp
                    <span class="font-medium">{{ $time->format() }}</span>
                @elseif($step->duration_type === 'distance')
                    @php
                        $distance = \App\ValueObjects\DistanceValue::fromMeters($step->duration_value);
                    @endphp
                    <span class="font-medium">{{ $distance->format() }}</span>
                @else
                    <span class="font-medium">Press Lap</span>
                @endif
            </div>

            <!-- Target -->
            <div class="text-gray-600">
                @if($step->target_type === 'heart_rate')
                    @if($step->target_mode === 'zone')
                        HR Zone {{ $step->target_zone }}
                    @else
                        HR {{ $step->target_low }}-{{ $step->target_high }} bpm
                    @endif
                @elseif($step->target_type === 'pace')
                    @if($step->target_mode === 'zone')
                        Pace Zone {{ $step->target_zone }}
                    @else
                        @php
                            $lowPace = \App\ValueObjects\PaceValue::fromSecondsPerKm($step->target_low);
                            $highPace = \App\ValueObjects\PaceValue::fromSecondsPerKm($step->target_high);
                        @endphp
                        Pace {{ $lowPace->format() }}-{{ $highPace->format() }}
                    @endif
                @else
                    No target
                @endif
            </div>
        </div>

        @if($step->notes)
            <div class="mt-1 text-xs text-gray-500">{{ $step->notes }}</div>
        @endif
    </div>

    <div class="flex gap-1">
        <flux:button wire:click="openStepModal(null, {{ $step->id }})" size="xs" variant="ghost">
            Edit
        </flux:button>
        <flux:button wire:click="deleteStep({{ $step->id }})" wire:confirm="Are you sure?" size="xs" variant="ghost">
            Delete
        </flux:button>
    </div>
</div>
