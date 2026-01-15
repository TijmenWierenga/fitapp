---
name: fix-view-duplication
description: Identify and extract repeated code patterns in Blade templates into reusable components. Use when views have duplicate HTML structures, repeated form patterns, or similar Blade code across multiple files.
allowed-tools: Read, Grep, Glob, Write, Edit, Bash
---

# Fixing View Template Duplication

## Purpose

Identify repeated code patterns in Laravel Blade templates and extract them into reusable components to improve maintainability.

## Process

### Step 1: Identify Duplication

Scan `resources/views/` for repeated patterns:

1. Use `Glob` to find all Blade templates: `resources/views/**/*.blade.php`
2. Read related views (e.g., dashboard components, similar pages)
3. Look for:
   - Similar HTML structures across multiple views
   - Repeated table rows or list items
   - Identical empty states
   - Duplicated badge/status logic
   - Repeated form field groups

### Step 2: Check Existing Components

Before creating new components, review what already exists:

```
resources/views/components/
├── empty-state.blade.php          # Empty state with icon, message, optional action
├── workout-schedule-badge.blade.php # Today/Tomorrow/relative date badge
├── workout-step-row.blade.php     # Step table row with duration/target
├── workout-repeat-header.blade.php # Repeat block header row
├── step-card.blade.php            # Step card for workout builder
└── ...
```

Also check if Flux UI provides a suitable component (preferred).

### Step 3: Extract Components

Create simple Blade components in `resources/views/components/`:

**Empty state component:**
```blade
@props(['icon' => 'document', 'message'])

<div class="flex flex-col items-center justify-center py-8 text-center">
    <flux:icon :name="$icon" class="size-12 text-zinc-400 dark:text-zinc-600 mb-3" />
    <flux:text class="text-zinc-500 dark:text-zinc-400">
        {{ $message }}
    </flux:text>
    @if($slot->isNotEmpty())
        <div class="mt-4">
            {{ $slot }}
        </div>
    @endif
</div>
```

**Badge with conditional logic:**
```blade
@props(['scheduledAt'])

@php
    $color = match(true) {
        $scheduledAt->isToday() => 'green',
        $scheduledAt->isTomorrow() => 'blue',
        default => 'zinc',
    };
@endphp

<flux:badge :color="$color" size="sm">
    @if($scheduledAt->isToday())
        Today
    @elseif($scheduledAt->isTomorrow())
        Tomorrow
    @else
        {{ $scheduledAt->diffForHumans() }}
    @endif
</flux:badge>
```

**Table row component:**
```blade
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
```

### Step 4: Refactor Views

Replace duplicated code with component usage:

```blade
{{-- Empty state --}}
<x-empty-state icon="calendar" message="No upcoming workouts">
    <flux:button href="{{ route('workouts.create') }}">Create</flux:button>
</x-empty-state>

{{-- Schedule badge --}}
<x-workout-schedule-badge :scheduled-at="$workout->scheduled_at" />

{{-- Step rows --}}
<x-workout-step-row :step="$step" />
<x-workout-step-row :step="$child" indented />

{{-- Repeat header --}}
<x-workout-repeat-header :repeat-count="$step->repeat_count" />
```

### Step 5: Verify Changes

1. Run `vendor/bin/pint --dirty` to fix formatting
2. Run relevant tests: `php artisan test --filter=Dashboard` or similar
3. Verify all affected views render correctly

## Guidelines

- **Prefer Flux UI** - Use `<flux:*>` components when available
- **Use kebab-case for props** - `:scheduled-at` not `:scheduledAt`
- **Use `@class` directive** - For conditional classes
- **Support slots** - Use `$slot->isNotEmpty()` for optional content
- **Keep components focused** - One responsibility per component
- **Avoid over-abstraction** - Only extract patterns that repeat 2+ times

## Common Patterns to Extract

| Pattern | Component Name | Props |
|---------|---------------|-------|
| Empty state | `empty-state` | `icon`, `message`, slot |
| Date badge | `workout-schedule-badge` | `scheduledAt` |
| Table row | `workout-step-row` | `step`, `indented` |
| Repeat header | `workout-repeat-header` | `repeatCount` |
| Form field | `form-input` | `name`, `label`, `type` |

## Naming Conventions

- Use descriptive, hyphenated names: `workout-step-row`, not `step-row`
- Prefix with domain when specific: `workout-*` for workout-related components
- Keep generic components generic: `empty-state`, not `workout-empty-state`
