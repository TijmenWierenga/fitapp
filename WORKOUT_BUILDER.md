# Workout Builder Documentation

## Overview

The Workout Builder is a Garmin-Connect-style interface for creating and editing structured running workouts. It supports ordered workout steps, nested repeats, and human-readable inputs for time, pace, and distance.

## Features

### Domain Model

#### Workout
- `id`: Unique identifier
- `name`: Workout name
- `sport`: Always set to "running" (v1)
- `user_id`: Associated user
- `scheduled_at`: When the workout is scheduled
- `completed_at`: When the workout was completed (nullable)

#### WorkoutStep (Tree Node)
Each step represents either a workout segment or a repeat block:

**Common Fields:**
- `id`: Unique identifier
- `workout_id`: Parent workout
- `parent_step_id`: Parent step (for nested repeats, nullable)
- `sort_order`: Order within parent
- `step_kind`: warmup | run | recovery | cooldown | repeat
- `intensity`: warmup | active | rest | cooldown
- `name`: Optional custom name
- `notes`: Optional notes

**Normal Step Fields (when step_kind != repeat):**
- `duration_type`: time | distance | lap_press
- `duration_value`: Total seconds (time) or meters (distance), null for lap_press
- `target_type`: none | heart_rate | pace
- `target_mode`: zone | range (when target_type is not none)
- `target_zone`: 1-5 (for zone targeting)
- `target_low/high`: BPM or seconds/km (for range targeting)

**Repeat Step Fields (when step_kind == repeat):**
- `repeat_count`: Number of repetitions (≥2)
- `skip_last_recovery`: Whether to skip the last recovery step

### Validation Rules

#### Workout Structure
- Workout must have at least 1 step
- Normal steps cannot have children
- Repeat steps must have at least 1 child
- Max nesting depth: 2 (workout → repeat → normal)
- Repeat blocks cannot be nested inside other repeat blocks

#### Duration Constraints
- **Time**: 10 seconds to 6 hours (21,600 seconds)
- **Distance**: 10 meters to 100 km (100,000 meters), must be divisible by 10
- **Lap Press**: No value stored

#### Target Constraints
- **HR Zone**: 1-5
- **HR Range**: 40-230 bpm, low < high
- **Pace Zone**: 1-5
- **Pace Range**: 120-900 seconds/km (2:00-15:00 /km), low < high
- When target_type=none, all target fields must be null

### Human-Readable Inputs

The builder uses dedicated value objects to handle conversions between UI and storage:

#### TimeValue
- **Input**: Minutes + seconds (0-59)
- **Storage**: Total seconds (integer)
- **Display**: "m:ss"
- **Example**: 5 min 30 sec → 330 seconds

#### PaceValue
- **Input**: Minutes + seconds per km (0-59)
- **Storage**: Seconds per km (integer)
- **Display**: "m:ss /km"
- **Example**: 4 min 30 sec/km → 270 seconds/km

#### DistanceValue
- **Input**: Kilometers + tens of meters (0-99)
- **Storage**: Total meters (integer, divisible by 10)
- **Display**: "x.xxx km"
- **Examples**:
  - 4 km, 55 tens → 4550 meters → "4.550 km"
  - 0 km, 1 ten → 10 meters → "0.010 km"

**Important**: No floating-point math is used for distances. All calculations use integers.

## Usage

### Creating a New Workout

1. Navigate to "Workout Builder" in the sidebar
2. Enter workout details (name, scheduled date/time)
3. Click "Save Workout Details" to create the workout
4. Add steps using the "+ Add Step" or "+ Add Repeat Block" buttons

### Adding Steps

**Normal Step:**
1. Click "+ Add Step"
2. Select step type (Warmup, Run, Recovery, Cooldown)
3. Choose duration type and enter values
4. Optionally set a target (HR or Pace, Zone or Range)
5. Click "Add Step"

**Repeat Block:**
1. Click "+ Add Repeat Block" to create with defaults (2x with 1km run + 1min recovery)
2. Or click "+ Add Step" → select "Repeat Block"
3. Set repeat count
4. After creation, click "+ Add Child" to add steps inside the repeat

### Editing Steps

1. Click "Edit" on any step card
2. Modify the fields
3. Click "Update Step"

### Default Repeat Block

When using "+ Add Repeat Block", it creates:
- Repeat 2x
- Child 1: Run 1.000 km (no target)
- Child 2: Recovery 1:00 (no target)

## API

### Models

```php
// Create a workout
$workout = Workout::create([
    'user_id' => $user->id,
    'name' => 'Morning Run',
    'sport' => 'running',
    'scheduled_at' => now()->addDay(),
]);

// Add a warmup step
$workout->allSteps()->create([
    'sort_order' => 0,
    'step_kind' => 'warmup',
    'intensity' => 'warmup',
    'duration_type' => 'time',
    'duration_value' => 600, // 10 minutes
    'target_type' => 'none',
]);

// Add a repeat block
$repeat = $workout->allSteps()->create([
    'sort_order' => 1,
    'step_kind' => 'repeat',
    'intensity' => 'active',
    'repeat_count' => 5,
]);

// Add child to repeat
$workout->allSteps()->create([
    'parent_step_id' => $repeat->id,
    'sort_order' => 0,
    'step_kind' => 'run',
    'intensity' => 'active',
    'duration_type' => 'distance',
    'duration_value' => 1000, // 1 km
    'target_type' => 'pace',
    'target_mode' => 'range',
    'target_low' => 240, // 4:00 /km
    'target_high' => 270, // 4:30 /km
]);

// Duplicate a workout
$duplicated = $workout->duplicate(now()->addWeek());
```

### Value Objects

```php
use App\ValueObjects\TimeValue;
use App\ValueObjects\PaceValue;
use App\ValueObjects\DistanceValue;

// Time conversions
$time = new TimeValue(5, 30); // 5 min 30 sec
$seconds = $time->toSeconds(); // 330
$display = $time->format(); // "5:30"

$time = TimeValue::fromSeconds(330);
echo $time->minutes; // 5
echo $time->seconds; // 30

// Pace conversions
$pace = new PaceValue(4, 30); // 4:30 /km
$secondsPerKm = $pace->toSecondsPerKm(); // 270
$display = $pace->format(); // "4:30 /km"

// Distance conversions
$distance = new DistanceValue(4, 55); // 4 km, 55 tens of meters
$meters = $distance->toMeters(); // 4550
$display = $distance->format(); // "4.550 km"

$distance = DistanceValue::fromMeters(4550);
echo $distance->kilometers; // 4
echo $distance->tensOfMeters; // 55
```

## Testing

Run the test suite:
```bash
php artisan test
```

Run specific test files:
```bash
php artisan test tests/Unit/ValueObjects/TimeValueTest.php
php artisan test tests/Feature/Models/WorkoutStepTest.php
```

## Database

Migrations are included for:
- `workout_steps` table (2026_01_07_184500_create_workout_steps_table.php)
- Adding `sport` field to workouts (2026_01_07_184501_add_sport_to_workouts_table.php)

Run migrations:
```bash
php artisan migrate
```

Seed sample data:
```bash
php artisan db:seed --class=WorkoutSeeder
```

## Routes

- `/workouts/builder` - Create new workout
- `/workouts/builder/{id}` - Edit existing workout

## Future Enhancements (Not in v1)

- FIT file export
- Support for other sports (cycling, swimming, etc.)
- Custom pace/HR zones configuration
- Workout templates library
- Social sharing
- GPS route integration
