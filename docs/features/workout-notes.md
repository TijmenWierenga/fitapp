# Feature: Workout Notes

## Overview

Add the ability for users to attach notes to their workouts. Notes provide a flexible way for users to add free-form content to workouts, including custom instructions for workouts without structured steps, personal reminders, or any contextual information relevant to the workout.

## Problem Statement

Users need a way to add contextual information to their workouts that doesn't fit within the structured step-based workout builder. This includes:
- Instructions for workouts that don't use the workout builder (e.g., "Run 5K at easy pace")
- Personal reminders (e.g., "bring running shoes for my buddy")
- Additional context about workout conditions, goals, or modifications

Currently, the application only supports structured steps with a `notes` field that is not exposed in the UI. There is no way to add general notes at the workout level.

## User Stories

**As a user**, I want to add notes to a workout when creating it, so that I can include custom instructions or reminders.

**As a user**, I want to edit notes on an existing workout (before completion), so that I can update instructions or add new information.

**As a user**, I want to see workout notes on my next workout card, so that I'm immediately aware of any special instructions or reminders.

**As a user**, I want to view notes on the workout detail page, so that I can review all information about a workout.

**As a user**, I want to see notes on completed workouts, so that I can reference what I did or any observations I made.

**As a user**, I want notes to be copied when I duplicate a workout, so that I don't have to re-enter the same information.

## Functional Requirements

### Core Requirements

1. **Database Schema**
   - Add a `notes` column to the `workouts` table
   - Column type: `text` (nullable)
   - Default value: `null`

2. **Model Updates**
   - Add `notes` to the `$fillable` array on the `Workout` model
   - Update PHPDoc block to include `@property string|null $notes`

3. **Workout Creation**
   - Add a notes textarea field to the workout creation form
   - Notes are optional and can be left blank
   - Notes should accept multi-line input with preserved line breaks

4. **Workout Editing**
   - Add notes textarea field to the workout edit form
   - Only editable when `canBeEdited()` returns true (before completion)
   - Pre-populate with existing notes when editing

5. **Notes Display - Next Workout Card**
   - Display notes in the NextWorkout component (dashboard)
   - Only show notes if they exist (conditional rendering)
   - Preserve line breaks in display
   - Keep notes visually distinct from structured step information

6. **Notes Display - Workout Detail View**
   - Display notes on the workout show page
   - Only show notes section if notes exist
   - Preserve line breaks in display
   - Position notes appropriately in the layout (suggest below workout metadata, above steps)

7. **Notes Display - Completed Workouts**
   - Display notes on completed workout detail pages
   - Same display rules as active workouts (show only if present)
   - Notes should be read-only on completed workouts

8. **Workout Duplication**
   - Include notes when duplicating a workout
   - Update the `duplicate()` method on the Workout model to copy the notes field

9. **Validation**
   - Notes are optional (not required)
   - No strict character limit enforced at validation level
   - Basic XSS protection through Laravel's automatic escaping

### Optional Enhancements

These are explicitly out of scope for the initial implementation but could be considered for future iterations:

- Character counter or recommended length guidance
- Rich text formatting (bold, italic, lists)
- Notes templates or quick-insert snippets
- Search functionality to find workouts by note content
- Visual indicator (icon/badge) showing a workout has notes

## User Interface

### Form Input (Create/Edit)

**Component**: Flux UI textarea component

**Label**: "Notes" (optional indicator in label or helper text)

**Placeholder**: "Add notes, instructions, or reminders for this workout..."

**Attributes**:
- Multi-line textarea
- Auto-resize or fixed height (minimum 3-4 rows)
- Follows existing form styling conventions

**Example UI Structure**:
```blade
<flux:field>
    <flux:label>Notes (optional)</flux:label>
    <flux:textarea
        wire:model="notes"
        placeholder="Add notes, instructions, or reminders for this workout..."
        rows="4"
    />
</flux:field>
```

### Display Format

**Next Workout Card**:
- Display notes in a distinct visual section
- Use muted text color or lighter background to differentiate from primary content
- Preserve line breaks using `white-space: pre-wrap` or `nl2br()`
- Consider using a Flux UI callout or card section

**Workout Detail Page**:
- Display in a dedicated section with a clear heading ("Notes" or "Workout Notes")
- Position after workout metadata (name, date, sport) but before steps
- Use similar styling as step display for consistency
- Only render the section if notes exist

**Completed Workouts**:
- Same display format as active workouts
- No edit capability (read-only)

## Data Model

### Database Migration

**Migration Name**: `add_notes_to_workouts_table`

**Schema Changes**:
```php
Schema::table('workouts', function (Blueprint $table) {
    $table->text('notes')->nullable()->after('sport');
});
```

**Rollback**:
```php
Schema::table('workouts', function (Blueprint $table) {
    $table->dropColumn('notes');
});
```

### Model Updates

**Workout Model**:
- Add `'notes'` to `$fillable` array
- Add `@property string|null $notes` to PHPDoc block
- Update `duplicate()` method to include `'notes' => $this->notes` in the create array

**Factory Updates**:
- WorkoutFactory should generate optional notes for testing
- Use Faker to generate realistic note content
- Make notes nullable in factory (e.g., 50% chance of having notes)

## Business Rules

1. Notes are always optional - workouts can exist without notes
2. Notes can only be created/edited on workouts that are not completed
3. Notes are preserved when a workout is marked as completed
4. Notes are copied when a workout is duplicated
5. Empty notes (empty string or whitespace-only) should be stored as `null`
6. Line breaks and whitespace should be preserved in storage and display

## Validation Rules

### Create/Edit Workout Validation

**Notes Field**:
- `nullable` - field is optional
- `string` - must be a string if provided
- `max:65535` - text column limit (though this is unlikely to be reached)

**Sanitization**:
- Trim whitespace from beginning and end
- Convert empty strings to null before saving
- Rely on Laravel's automatic XSS protection (HTML entities escaping in Blade)

## Error Handling

### Expected Errors

1. **Database Error**: If notes exceed database column size
   - **Handling**: Validation prevents this scenario
   - **User Message**: "Notes are too long. Please shorten your notes."

2. **Authorization Error**: User tries to edit notes on another user's workout
   - **Handling**: Existing authorization checks prevent this
   - **User Message**: 403 Forbidden (existing behavior)

3. **Invalid State**: User tries to edit notes on a completed workout
   - **Handling**: Form should not be accessible (UI prevents this)
   - **User Message**: No specific message needed (prevented by UI)

### Edge Cases

1. **Very Long Notes**: While technically possible, no hard limit is enforced initially. Database column can handle up to 65KB of text.

2. **Special Characters**: All special characters, line breaks, and Unicode should be preserved and safely escaped during display.

3. **Concurrent Edits**: Standard Livewire behavior handles this (last save wins).

## Acceptance Criteria

### Must Have

- [ ] Database migration adds `notes` column to `workouts` table
- [ ] Workout model includes `notes` in fillable properties
- [ ] Notes textarea appears on workout creation form
- [ ] Notes textarea appears on workout edit form (when editable)
- [ ] Notes display on NextWorkout component when present
- [ ] Notes display on workout detail page when present
- [ ] Notes display on completed workout detail page when present
- [ ] Notes are not editable on completed workouts
- [ ] Notes are copied when duplicating a workout
- [ ] Line breaks are preserved in notes display
- [ ] Empty notes are stored as `null` (not empty strings)
- [ ] Validation allows optional notes field
- [ ] Factory generates test workouts with and without notes

### Testing Requirements

- [ ] Feature test: Create workout with notes
- [ ] Feature test: Create workout without notes (null value)
- [ ] Feature test: Edit workout to add notes
- [ ] Feature test: Edit workout to modify existing notes
- [ ] Feature test: Edit workout to remove notes (set to null)
- [ ] Feature test: Cannot edit notes on completed workout
- [ ] Feature test: Duplicate workout copies notes
- [ ] Feature test: Notes display on next workout card
- [ ] Feature test: Notes display on workout detail page
- [ ] Feature test: Notes display on completed workout page
- [ ] Unit test: Empty string notes are converted to null
- [ ] Unit test: Whitespace is trimmed from notes

## Technical Notes

### Implementation Order

1. Create and run database migration
2. Update Workout model (fillable, PHPDoc)
3. Update WorkoutFactory
4. Update duplicate() method to include notes
5. Add notes field to create workout form
6. Add notes field to edit workout form
7. Add notes display to NextWorkout component/view
8. Add notes display to workout show view
9. Add notes display to completed workouts view
10. Write comprehensive tests
11. Run Pint to format code

### Livewire Components Affected

- `App\Livewire\Workout\Builder` (or equivalent create/edit component)
- `App\Livewire\Dashboard\NextWorkout`
- `App\Livewire\Workout\Show`
- `App\Livewire\Dashboard\CompletedWorkouts` (if it shows detail)

### Views Affected

- `resources/views/livewire/workout/builder.blade.php` (or create form)
- `resources/views/livewire/dashboard/next-workout.blade.php`
- `resources/views/livewire/workout/show.blade.php`

### Flux UI Components to Use

- `<flux:textarea>` for input
- `<flux:field>` and `<flux:label>` for form structure
- Consider `<flux:card>` or `<flux:separator>` for notes display sections

### Display Formatting

For preserving line breaks in displayed notes, use one of:
- CSS: `white-space: pre-wrap` class
- Blade helper: `{!! nl2br(e($workout->notes)) !!}`
- Or Flux UI component that handles multiline text

### Performance Considerations

- Notes should be eagerly loaded when displaying workouts (already included in typical `->with()` calls)
- No additional queries needed if notes are included in existing eager loads
- Text column is indexed automatically but doesn't need full-text search initially

## Out of Scope

The following features are explicitly NOT included in this initial implementation:

1. Rich text formatting (bold, italic, bullet points)
2. Markdown support
3. Notes templates or pre-defined snippets
4. Character counter UI
5. Search/filter workouts by notes content
6. Notes on individual steps (step-level notes already exist in schema but unused)
7. Visual indicator (badge/icon) that a workout has notes
8. Notes categories or tags
9. Sharing notes with other users
10. Version history or edit tracking for notes
11. Notes on workout templates (separate from workout instances)

## Open Questions

None - all requirements have been clarified.

## Additional Context

### Existing Code References

- **Workout Model**: `/Users/tijmenwierenga/www/tijmenwierenga/fitapp/app/Models/Workout.php`
- **Workout Migration**: `/Users/tijmenwierenga/www/tijmenwierenga/fitapp/database/migrations/2026_01_05_191830_create_workouts_table.php`
- **NextWorkout Component**: `/Users/tijmenwierenga/www/tijmenwierenga/fitapp/app/Livewire/Dashboard/NextWorkout.php`
- **Show Component**: `/Users/tijmenwierenga/www/tijmenwierenga/fitapp/app/Livewire/Workout/Show.php`

### Related Features

- Steps already have a `notes` field in the database that is currently unused in the UI
- Consider future alignment between workout-level and step-level notes
