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

- Similar HTML structures across multiple views
- Repeated form field groups
- Identical card, modal, or list layouts
- Duplicated styling patterns with the same Tailwind classes

### Step 2: Check Existing Components

Before creating new components:

1. Review `resources/views/components/` for existing components
2. Check if Flux UI provides a suitable component (preferred)
3. Look at sibling components for naming and structure conventions

### Step 3: Extract Components

Create Blade components following project conventions:

**Simple components** - Create in `resources/views/components/`:

```blade
@props(['name', 'label' => null, 'type' => 'text'])

<div>
    @if($label)
        <label for="{{ $name }}">{{ $label }}</label>
    @endif
    <input type="{{ $type }}" id="{{ $name }}" name="{{ $name }}" {{ $attributes }} />
</div>
```

**Class-based components** - Use artisan:

```bash
php artisan make:component ComponentName
```

### Step 4: Refactor Views

Replace duplicated code with component usage:

```blade
{{-- Before: duplicated markup --}}
<div class="mb-4">
    <label>Name</label>
    <input type="text" name="name" class="..." />
</div>

{{-- After: reusable component --}}
<x-form-input name="name" label="Name" />
```

### Step 5: Verify Changes

1. Run `vendor/bin/pint --dirty` to fix formatting
2. Run relevant tests: `php artisan test --filter=ViewName`
3. Verify views render correctly

## Guidelines

- **Prefer Flux UI** - Use `<flux:*>` components when available
- **Use props for variation** - Pass data via `@props` directive
- **Follow conventions** - Match existing component structure and naming
- **Keep components focused** - One responsibility per component
- **Avoid over-abstraction** - Only extract patterns that repeat 3+ times

## Common Patterns to Extract

- Form field groups (input + label + error)
- Card containers with consistent styling
- Table row templates
- Modal dialogs
- Status badges
- Navigation items
- Empty state displays
