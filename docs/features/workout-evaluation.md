# Feature: Workout Evaluation

## Overview
Add the ability for users to evaluate their completed workouts using a two-metric system: Rate of Perceived Exertion (RPE) and an overall feeling rating. This feature captures immediate post-workout feedback to help users track their training load and subjective experience over time. The evaluation is prompted immediately when a workout is marked as completed, similar to how Garmin handles workout evaluations.

## Problem Statement
Users need a simple, quick way to record how difficult a workout felt (RPE) and their general feeling about the session. This data is valuable for:
- Tracking training load and recovery patterns
- Identifying overtraining or undertraining trends
- Understanding which workouts feel good vs challenging
- Making data-driven adjustments to training plans

Currently, there is no structured way to capture this subjective feedback immediately after completing a workout.

## User Stories

### Primary User Story
As a fitness enthusiast, I want to quickly evaluate my workout when I mark it as completed, so that I can track how my training feels over time without spending extra time on detailed note-taking.

### Specific Scenarios
1. As a user completing a scheduled workout, I want to be prompted for my RPE and feeling rating immediately after marking it complete, so that I can record my feedback while the experience is fresh.

2. As a user reviewing my workout history, I want to see my RPE and feeling ratings alongside completed workouts, so that I can identify patterns in my training experience.

3. As a user who forgets to evaluate a workout, I want the evaluation to be required when marking a workout complete, so that I build consistent tracking habits.

## Functional Requirements

### Core Requirements

1. **Database Schema Changes**
   - Add `rpe` column to `workouts` table (integer, nullable, range 1-10)
   - Add `feeling` column to `workouts` table (integer, nullable, range 1-5)
   - Both fields should only be populated when a workout is marked as completed

2. **Evaluation Modal/Dialog**
   - Display a modal immediately when user clicks "Mark as Completed" button
   - Modal should contain:
     - RPE slider/input (1-10 scale)
     - Feeling rating slider/input (1-5 scale)
     - Clear labels explaining each metric
     - Submit button to complete the evaluation
     - Cancel button to dismiss without completing workout
   - Modal cannot be dismissed without either:
     - Providing both ratings and submitting
     - Cancelling (which does NOT mark workout as completed)

3. **RPE Scale Implementation**
   - Use modified 1-10 RPE scale
   - Scale labels:
     - 1-2: Very Easy
     - 3-4: Easy
     - 5-6: Moderate
     - 7-8: Hard
     - 9-10: Maximum Effort
   - Default/no pre-selection required

4. **Feeling Scale Implementation**
   - Generic 1-5 scale without specific labels
   - Display as simple numeric scale (1, 2, 3, 4, 5)
   - Allow users to interpret meaning themselves
   - Default/no pre-selection required

5. **Workout Completion Flow**
   - When "Mark as Completed" button is clicked:
     1. Open evaluation modal
     2. User provides RPE and feeling rating
     3. User clicks submit
     4. Workout is marked complete with `completed_at` timestamp
     5. RPE and feeling values are saved
     6. Modal closes
     7. Success feedback shown
     8. Page refreshes/updates to show completed status
   - If user cancels modal:
     - Workout remains not completed
     - No data is saved
     - User returns to workout view

6. **Display of Evaluation Data**
   - Show RPE and feeling ratings in the workout detail view for completed workouts
   - Display in the Details card alongside completion timestamp
   - Format: "RPE: 7/10" and "Feeling: 4/5"

7. **Model Updates**
   - Add `rpe` and `feeling` to `Workout` model fillable array
   - Add appropriate casts (integer)
   - Add PHPDoc annotations for the new properties
   - Update `markAsCompleted()` method signature to accept RPE and feeling parameters

8. **Livewire Component Updates**
   - Update `App\Livewire\Workout\Show` component
   - Add modal state management (open/closed)
   - Add form properties for RPE and feeling
   - Update `markAsCompleted()` method to:
     - Open evaluation modal instead of immediately completing
     - Accept form submission with RPE and feeling
     - Validate inputs (required, integer, within range)
     - Save evaluation data when workout is completed

### Optional Enhancements
These are explicitly out of scope for the initial implementation but noted for future consideration:

- Editing evaluation after submission
- Analytics/charts showing RPE trends over time
- Workout difficulty predictions based on historical RPE
- Comparison of planned vs actual difficulty
- Notes specific to the evaluation (separate from workout notes)

## User Interface

### Evaluation Modal Design
The modal should follow Flux UI patterns and include:

**Modal Header**
- Title: "How was your workout?"
- Close button (acts as cancel)

**Modal Body**
- **RPE Section**
  - Label: "Rate of Perceived Exertion (RPE)"
  - Helper text: "How hard did this workout feel?"
  - Slider component (1-10 range) with tick marks
  - Current value display showing number and descriptive label
  - Scale markers: Very Easy (1-2) | Easy (3-4) | Moderate (5-6) | Hard (7-8) | Maximum (9-10)

- **Feeling Section**
  - Label: "Overall Feeling"
  - Helper text: "How did you feel during this workout?"
  - Slider component (1-5 range) with tick marks
  - Current value display showing number only
  - Simple numeric markers: 1 | 2 | 3 | 4 | 5

**Modal Footer**
- Cancel button (ghost variant) - left aligned
- Submit button (primary variant) - right aligned, text: "Complete Workout"
- Submit button disabled until both values are selected

### Workout Detail View Updates
In the Details card, after the completion timestamp:
```
Completed 2 hours ago
RPE: 7/10 (Hard)
Feeling: 4/5
```

Display should use existing Flux UI text components and icons for consistency.

## Data Model

### Database Migration
```php
Schema::table('workouts', function (Blueprint $table) {
    $table->unsignedTinyInteger('rpe')->nullable()->after('completed_at');
    $table->unsignedTinyInteger('feeling')->nullable()->after('rpe');
});
```

### Workout Model Updates
```php
protected $fillable = [
    // ... existing fields
    'completed_at',
    'rpe',
    'feeling',
];

protected function casts(): array
{
    return [
        // ... existing casts
        'rpe' => 'integer',
        'feeling' => 'integer',
    ];
}

/**
 * @property int|null $rpe
 * @property int|null $feeling
 */
```

## Business Rules

1. **Evaluation Requirement**
   - Both RPE and feeling must be provided to mark a workout as completed
   - Evaluation is required; there is no "skip" option
   - Cancelling the evaluation modal leaves the workout uncompleted

2. **Validation Rules**
   - RPE: required, integer, between 1 and 10 inclusive
   - Feeling: required, integer, between 1 and 5 inclusive
   - Both fields can only be set when workout is being marked as completed
   - Once completed, evaluation cannot be edited (v1 limitation)

3. **Evaluation Timing**
   - Evaluation is only prompted when marking a workout as completed
   - "Mark as Completed" button is only available for:
     - Workouts that are not already completed
     - Workouts scheduled for today or in the past

4. **Data Integrity**
   - RPE and feeling should only have values if `completed_at` is not null
   - If a workout is not completed, these fields must be null

## API/Integration Requirements
Not applicable - this is a self-contained feature with no external integrations.

## Security & Authorization

1. **Authorization**
   - Users can only evaluate their own workouts
   - Existing authorization check in `Show` component's `mount()` method applies
   - No additional authorization changes needed

2. **Data Privacy**
   - Workout evaluations are private to the user who owns the workout
   - No sharing or visibility to other users

## Validation Rules

### Form Request Validation
Create `App\Http\Requests\CompleteWorkoutRequest` with:

```php
public function rules(): array
{
    return [
        'rpe' => ['required', 'integer', 'min:1', 'max:10'],
        'feeling' => ['required', 'integer', 'min:1', 'max:5'],
    ];
}

public function messages(): array
{
    return [
        'rpe.required' => 'Please rate how hard this workout felt.',
        'rpe.min' => 'RPE must be between 1 and 10.',
        'rpe.max' => 'RPE must be between 1 and 10.',
        'feeling.required' => 'Please rate how you felt during this workout.',
        'feeling.min' => 'Feeling must be between 1 and 5.',
        'feeling.max' => 'Feeling must be between 1 and 5.',
    ];
}
```

### Livewire Component Validation
The Livewire component should use inline validation rules:
```php
$this->validate([
    'rpe' => 'required|integer|min:1|max:10',
    'feeling' => 'required|integer|min:1|max:5',
]);
```

## Error Handling

1. **Validation Errors**
   - Display validation errors inline below each input field
   - Use Flux UI error styling
   - Prevent form submission until errors are resolved

2. **Save Failures**
   - If database update fails, show error notification
   - Keep modal open with user's input preserved
   - Allow user to retry or cancel

3. **Network Issues**
   - Livewire handles connection issues automatically
   - Show standard Livewire connection indicator

## Edge Cases

1. **User clicks "Mark as Completed" then navigates away**
   - Modal closes when component unmounts
   - Workout remains uncompleted
   - No data loss (workout stays in original state)

2. **User provides ratings then clicks Cancel**
   - Modal closes without saving
   - Workout remains uncompleted
   - Form state is cleared

3. **User refreshes page while modal is open**
   - Modal closes (component remounts)
   - Workout remains uncompleted
   - User can open modal again by clicking "Mark as Completed"

4. **Workout is already completed**
   - "Mark as Completed" button is not displayed
   - Evaluation cannot be edited (v1 limitation)
   - RPE and feeling display as read-only values

5. **Multiple rapid clicks on "Mark as Completed"**
   - Livewire's wire:loading should prevent duplicate submissions
   - Button should be disabled while modal is open

## Acceptance Criteria

### Must Have
1. Migration created and run successfully adding `rpe` and `feeling` columns
2. Modal appears when clicking "Mark as Completed" button
3. Modal contains RPE slider (1-10) with descriptive labels
4. Modal contains feeling slider (1-5) with numeric display
5. Both fields are required to submit the evaluation
6. Cancel button closes modal without completing workout
7. Submit button completes workout and saves evaluation data
8. Completed workout displays RPE and feeling values in details card
9. Validation prevents invalid values (out of range, non-integer, null)
10. All tests pass after implementation

### Testing Requirements
1. Feature test: marking workout as completed with valid evaluation saves both values
2. Feature test: attempting to complete without RPE fails validation
3. Feature test: attempting to complete without feeling fails validation
4. Feature test: RPE value outside 1-10 range fails validation
5. Feature test: feeling value outside 1-5 range fails validation
6. Feature test: canceling evaluation keeps workout uncompleted
7. Feature test: completed workout displays evaluation data
8. Browser test: modal opens when clicking "Mark as Completed"
9. Browser test: form submission workflow completes successfully
10. Browser test: cancel workflow works correctly

## Technical Notes

### Implementation Approach
1. Create migration for database schema changes
2. Update Workout model with new properties and casts
3. Create evaluation modal Blade component
4. Update Show Livewire component with modal state and validation
5. Update workout detail view to display evaluation data
6. Write feature tests for evaluation workflow
7. Write browser tests for modal interaction
8. Run Pint for code formatting
9. Verify all tests pass

### Flux UI Components to Use
- `flux:modal` for the evaluation dialog
- `flux:slider` for RPE and feeling inputs (if available, otherwise `flux:input` with type="range")
- `flux:button` for submit and cancel actions
- `flux:heading` for modal title
- `flux:text` for labels and helper text
- `flux:field` for form field grouping with validation errors

### Performance Considerations
- Modal rendering should be fast (client-side only, no API calls)
- Form submission is a single Livewire request
- No performance concerns expected

### Browser Compatibility
- Standard Livewire and Flux UI browser support applies
- Slider inputs are widely supported in modern browsers
- No special polyfills needed

## Out of Scope

The following items are explicitly NOT included in this initial implementation:

1. Editing evaluation after submission
2. Deleting or removing evaluation data
3. Evaluation analytics or trend visualization
4. Bulk evaluation (evaluating multiple workouts at once)
5. Evaluation reminders or notifications
6. Notes field specific to evaluation (separate from workout notes)
7. Additional metrics beyond RPE and feeling
8. Exporting evaluation data
9. Sharing evaluations with coaches or friends
10. Evaluation templates or presets
11. Voice input for evaluation
12. Integration with external fitness tracking services

## Open Questions

None - all requirements have been specified based on the initial requirements provided by the user.
