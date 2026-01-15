@props(['step', 'indented' => false])

<flux:table.row>
    <flux:table.cell @class(['pl-8!' => $indented])>
        <flux:text size="sm" @class(['truncate', 'font-medium' => !$indented])>
            {{ $step->name ?: ucfirst($step->step_kind->value) }}
        </flux:text>
    </flux:table.cell>
    <flux:table.cell>
        <flux:text size="sm">{{ \App\Support\Workout\StepSummary::duration($step) }}</flux:text>
    </flux:table.cell>
    <flux:table.cell>
        <flux:text size="sm">
            @if(\App\Support\Workout\StepSummary::target($step) !== 'No target')
                {{ \App\Support\Workout\StepSummary::target($step) }}
            @else
                -
            @endif
        </flux:text>
    </flux:table.cell>
</flux:table.row>
