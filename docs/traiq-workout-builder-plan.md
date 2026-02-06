# Traiq Workout Builder — Implementation Plan

## Overview

Refactor the current run-only workout builder into a universal, block-based workout architecture that supports all activity types (running, biking, strength, HIIT, cardio, and combinations). The system uses recursive nested blocks with polymorphic content types and includes per-muscle-group intensity tracking with snapshot-based recovery analysis.

---

## 1. Database Schema

### 1.1 Enums

Create the following enums (backed by string columns or PHP enums):

**MuscleGroup**
`chest`, `upper_back`, `shoulders`, `biceps`, `triceps`, `forearms`, `core`, `lower_back`, `quadriceps`, `hamstrings`, `glutes`, `calves`, `hip_flexors`, `cardiovascular`

**BlockType**
`group`, `interval`, `exercise_group`, `rest`, `note`

**IntervalIntensity**
`easy`, `moderate`, `threshold`, `tempo`, `vo2max`, `sprint`

**ExerciseGroupType**
`straight`, `superset`, `circuit`, `emom`, `amrap`

**MuscleRole**
`primary`, `secondary`, `stabilizer`

**ExerciseCategory**
`compound`, `isolation`, `cardio`, `mobility`

**MovementPattern**
`squat`, `hinge`, `push`, `pull`, `carry`, `rotation`, `core`, `other`

**Equipment**
`barbell`, `dumbbell`, `kettlebell`, `bodyweight`, `machine`, `cable`, `band`, `other`

### 1.2 Migrations (in order)

#### Migration 1: `create_exercises_table`

| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | PK |
| name | string | e.g. "Barbell Back Squat" |
| category | string (enum) | compound, isolation, cardio, mobility |
| equipment | string (enum) | barbell, dumbbell, bodyweight, etc. |
| movement_pattern | string (enum) | squat, hinge, push, pull, etc. |
| primary_muscles | json | Array of MuscleGroup values |
| secondary_muscles | json | Array of MuscleGroup values |
| timestamps | | |

Index on `name` (unique), `category`, `equipment`, `movement_pattern`.

#### Migration 2: `create_exercise_muscle_loads_table`

| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | PK |
| exercise_id | foreignId | FK → exercises, cascadeOnDelete |
| muscle_group | string (enum) | MuscleGroup value |
| role | string (enum) | primary, secondary, stabilizer |
| load_factor | float | 0.0–1.0 |
| timestamps | | |

Unique constraint on `[exercise_id, muscle_group]`.

#### Migration 3: `create_activity_muscle_loads_table`

| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | PK |
| activity | string | Activity type: run, bike, pool_swim, row, hike, etc. |
| muscle_group | string (enum) | MuscleGroup value |
| role | string (enum) | primary, secondary, stabilizer |
| load_factor | float | 0.0–1.0 |
| timestamps | | |

Unique constraint on `[activity, muscle_group]`.

#### Migration 4: `create_workout_blocks_table`

| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | PK |
| workout_id | foreignId | FK → workouts, cascadeOnDelete |
| parent_id | foreignId, nullable | FK → workout_blocks, cascadeOnDelete (self-referential) |
| type | string (enum) | group, interval, exercise_group, rest, note |
| position | integer | Ordering within parent or workout |
| label | string, nullable | e.g. "Warm-up", "Main Set" |
| repeat_count | integer, default 1 | How many times to repeat this block |
| rest_between_repeats_seconds | integer, nullable | Rest between repetitions of this block |
| blockable_type | string, nullable | Polymorphic type (for interval, exercise_group, rest, note) |
| blockable_id | unsignedBigInteger, nullable | Polymorphic ID |
| timestamps | | |

Indexes: `[workout_id, position]`, `[parent_id, position]`.

**Nesting depth constraint**: Enforce max 3 levels in application logic (see validation section).

#### Migration 5: `create_interval_blocks_table`

| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | PK |
| duration_seconds | integer, nullable | Time-based intervals |
| distance_meters | integer, nullable | Distance-based intervals |
| target_pace_seconds_per_km | integer, nullable | Target pace |
| target_heart_rate_zone | integer, nullable | HR zone 1-5 |
| intensity | string (enum) | easy, moderate, threshold, tempo, vo2max, sprint |
| timestamps | | |

At least one of `duration_seconds` or `distance_meters` must be set (validate in model).

#### Migration 6: `create_exercise_groups_table`

| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | PK |
| group_type | string (enum) | straight, superset, circuit, emom, amrap |
| rounds | integer, default 1 | Number of rounds through the exercises |
| rest_between_rounds_seconds | integer, nullable | |
| timestamps | | |

#### Migration 7: `create_exercise_entries_table`

| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | PK |
| exercise_group_id | foreignId | FK → exercise_groups, cascadeOnDelete |
| exercise_id | foreignId | FK → exercises |
| position | integer | Order within the group |
| sets | integer | Number of sets |
| reps | integer, nullable | Reps per set (nullable for timed exercises) |
| duration_seconds | integer, nullable | For timed exercises (planks, holds) |
| weight_kg | float, nullable | |
| rpe_target | integer, nullable | 1-10 |
| rest_between_sets_seconds | integer, nullable | |
| notes | text, nullable | |
| timestamps | | |

Index on `[exercise_group_id, position]`.

#### Migration 8: `create_rest_blocks_table`

| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | PK |
| duration_seconds | integer | |
| timestamps | | |

#### Migration 9: `create_note_blocks_table`

| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | PK |
| content | text | Coaching cues, phase markers |
| timestamps | | |

#### Migration 10: `create_workout_muscle_load_snapshots_table`

| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | PK |
| workout_id | foreignId | FK → workouts, cascadeOnDelete |
| muscle_group | string (enum) | MuscleGroup value |
| total_load | float | Computed total load for this muscle |
| source_breakdown | json | Detailed breakdown (see format below) |
| completed_at | datetime | Denormalized from workout for fast queries |
| created_at | timestamp | |

Unique constraint on `[workout_id, muscle_group]`. Index on `[completed_at]` for recovery queries.

**source_breakdown JSON format:**
```json
{
    "exercises": [
        {
            "exercise": "Barbell Back Squat",
            "sets": 4,
            "reps": 8,
            "weight_kg": 80,
            "load_contribution": 22.4
        }
    ],
    "intervals": [
        {
            "description": "800m @ threshold",
            "repeats": 4,
            "load_contribution": 8.2
        }
    ]
}
```

---

## 2. Eloquent Models

### 2.1 WorkoutBlock

```php
class WorkoutBlock extends Model
{
    protected $casts = [
        'type' => BlockType::class,
    ];

    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(WorkoutBlock::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(WorkoutBlock::class, 'parent_id')->orderBy('position');
    }

    public function nestedChildren(): HasMany
    {
        return $this->children()->with('nestedChildren', 'blockable');
    }

    public function blockable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Calculate nesting depth (1-based).
     * Root blocks are depth 1.
     */
    public function depth(): int
    {
        return $this->parent ? $this->parent->depth() + 1 : 1;
    }

    /**
     * Maximum allowed nesting depth.
     */
    public static function maxDepth(): int
    {
        return 3;
    }
}
```

### 2.2 Block Content Models

Each is its own model with its own table, related via polymorphism:

- `IntervalBlock` — morphOne back to WorkoutBlock
- `ExerciseGroup` — morphOne back to WorkoutBlock, hasMany ExerciseEntry
- `RestBlock` — morphOne back to WorkoutBlock
- `NoteBlock` — morphOne back to WorkoutBlock

### 2.3 ExerciseEntry

```php
class ExerciseEntry extends Model
{
    public function exerciseGroup(): BelongsTo
    {
        return $this->belongsTo(ExerciseGroup::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
```

### 2.4 Exercise

```php
class Exercise extends Model
{
    protected $casts = [
        'primary_muscles' => 'array',
        'secondary_muscles' => 'array',
        'category' => ExerciseCategory::class,
        'equipment' => Equipment::class,
        'movement_pattern' => MovementPattern::class,
    ];

    public function muscleLoads(): HasMany
    {
        return $this->hasMany(ExerciseMuscleLoad::class);
    }
}
```

### 2.5 Workout (update existing)

Add relationship to blocks:

```php
public function blocks(): HasMany
{
    return $this->hasMany(WorkoutBlock::class)
        ->whereNull('parent_id')
        ->orderBy('position');
}

public function allBlocks(): HasMany
{
    return $this->hasMany(WorkoutBlock::class);
}

public function blockTree(): HasMany
{
    return $this->blocks()->with('nestedChildren', 'blockable');
}

public function muscleLoadSnapshots(): HasMany
{
    return $this->hasMany(WorkoutMuscleLoadSnapshot::class);
}
```

---

## 3. Validation Rules

### 3.1 Nesting Depth Validation

Create a custom rule `MaxBlockDepth`:

```php
class MaxBlockDepth implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $parent = WorkoutBlock::find($value);
        if ($parent && $parent->depth() >= WorkoutBlock::maxDepth()) {
            $fail("Maximum nesting depth of " . WorkoutBlock::maxDepth() . " levels exceeded.");
        }
    }
}
```

Apply this rule whenever creating a block with a `parent_id`.

### 3.2 IntervalBlock Validation

At least one of `duration_seconds` or `distance_meters` must be provided.

### 3.3 ExerciseEntry Validation

At least one of `reps` or `duration_seconds` must be provided.

### 3.4 Block Type Consistency

- `group` type blocks must NOT have a blockable relation (they are containers only)
- `interval`, `exercise_group`, `rest`, `note` blocks MUST have a corresponding blockable relation
- Only `exercise_group` type blocks should have ExerciseEntry children (via the ExerciseGroup model)

---

## 4. Services

### 4.1 MuscleLoadCalculator

**Location:** `App\Services\MuscleLoadCalculator`

**Responsibility:** Calculate per-muscle-group load for a given workout, using current load factors.

**Method:** `calculate(Workout $workout): MuscleLoadSummary`

**Logic:**

1. Load the full block tree for the workout with eager loading
2. Recursively walk the tree
3. For each `ExerciseEntry`:
    - Calculate volume: `sets × (reps ?? 1)`
    - Calculate effort: `rpe_target / 10` if set, else `0.6` default
    - Look up `ExerciseMuscleLoad` records for the exercise
    - For each muscle: `load = volume × effort × load_factor × repeat_multiplier`
    - The `repeat_multiplier` is the product of all `repeat_count` values walking up the block tree to the root
4. For each `IntervalBlock`:
    - Determine the workout's activity type
    - Calculate effective duration in minutes (from duration_seconds, or estimate from distance_meters)
    - Look up `ActivityMuscleLoad` records for the activity
    - Apply intensity multiplier: easy=0.3, moderate=0.5, threshold=0.7, tempo=0.8, vo2max=0.9, sprint=1.0
    - For each muscle: `load = duration_minutes × intensity_multiplier × load_factor × repeat_multiplier`
5. Sum loads per muscle group across all blocks
6. Return `MuscleLoadSummary` containing per-muscle totals and source breakdowns

**MuscleLoadSummary value object:**

```php
class MuscleLoadSummary
{
    public function __construct(
        private array $loads // muscle_group => ['total' => float, 'sources' => array]
    ) {}

    public function all(): array;
    public function forMuscle(MuscleGroup $muscle): array;
    public function totalLoad(): float;
}
```

### 4.2 MuscleRecoveryService

**Location:** `App\Services\MuscleRecoveryService`

**Responsibility:** Query snapshot data to determine current fatigue/recovery status per muscle group.

**Method:** `getRecoveryStatus(User $user, ?Carbon $asOf = null): array`

**Logic:**

1. Query `WorkoutMuscleLoadSnapshot` for the user's workouts completed in the last 4 days
2. Group by `muscle_group`
3. For each muscle group, sum remaining fatigue:
    - `hours_since = completed_at → now() in hours`
    - `recovery_hours = recoveryHoursFor(total_load)`:
        - load < 30 → 24 hours (light)
        - load 30–70 → 48 hours (moderate)
        - load > 70 → 72 hours (heavy)
    - `remaining_fatigue = max(0, 1 - (hours_since / recovery_hours)) × total_load`
4. Return per-muscle status:

```php
[
    'quadriceps' => [
        'fatigue_score' => 42.5,
        'status' => 'recovering', // fresh (<= 20), recovering (20-50), fatigued (> 50)
        'ready_for_heavy' => false, // fatigue < 15
    ],
    // ...
]
```

**Method:** `suggestTargetMuscles(User $user): array`

Returns muscles sorted by readiness (lowest fatigue first), flagging which are ready for heavy work vs light work only.

### 4.3 WorkoutCompletionService (update existing)

**Location:** `App\Services\WorkoutCompletionService`

**Update:** After marking a workout as completed, calculate and store muscle load snapshots.

**Logic:**

1. Mark workout completed with RPE and feeling (existing logic)
2. Call `MuscleLoadCalculator::calculate($workout)`
3. For each muscle in the summary, create a `WorkoutMuscleLoadSnapshot` record with:
    - `workout_id`
    - `muscle_group`
    - `total_load`
    - `source_breakdown` (JSON)
    - `completed_at` (denormalized from workout)

### 4.4 Interval Duration Estimation

Distance-based intervals (e.g. "800m at threshold") need a duration estimate for load calculation. Use the user's target pace if set on the interval, otherwise fall back to sensible defaults per intensity level.

**Helper method on MuscleLoadCalculator:**

```php
private function estimateDurationMinutes(IntervalBlock $interval): float
{
    if ($interval->duration_seconds) {
        return $interval->duration_seconds / 60;
    }

    // Estimate from distance using target pace or default
    $paceSecondsPerKm = $interval->target_pace_seconds_per_km
        ?? self::DEFAULT_PACE_PER_INTENSITY[$interval->intensity->value];

    return ($interval->distance_meters / 1000) * $paceSecondsPerKm / 60;
}
```

**Default pace lookup (seconds per km):**

| Intensity | Default pace/km | Rationale |
|---|---|---|
| easy | 360 (6:00) | Conversational pace |
| moderate | 330 (5:30) | Steady state |
| threshold | 270 (4:30) | Lactate threshold |
| tempo | 300 (5:00) | Sustainable hard effort |
| vo2max | 240 (4:00) | Near max aerobic |
| sprint | 210 (3:30) | All-out short effort |

These defaults can be overridden per user in a future phase by deriving pace zones from race results or fitness test data.

---

## 5. Data Population Strategy

There are three categories of data to populate, each with a different approach.

### 5.1 Activity Muscle Loads — Seeder (static, ships with app)

This is a small, finite dataset (~10-15 activity types). A single seeder with hardcoded values is sufficient. These rarely change.

**Seeder:** `ActivityMuscleLoadSeeder`

| Activity | Primary muscles | Secondary muscles |
|---|---|---|
| run | calves (0.8), quadriceps (0.7), hamstrings (0.6), cardiovascular (1.0) | glutes (0.5), hip_flexors (0.4), core (0.2) |
| bike | quadriceps (0.8), cardiovascular (0.9) | glutes (0.6), hamstrings (0.4), calves (0.3) |
| pool_swim | upper_back (0.8), shoulders (0.7), cardiovascular (1.0) | triceps (0.5), core (0.5), chest (0.3) |
| row | upper_back (0.7), quadriceps (0.6), cardiovascular (0.9) | hamstrings (0.5), biceps (0.5), core (0.4) |
| hike | quadriceps (0.5), calves (0.5), cardiovascular (0.7) | glutes (0.4), hamstrings (0.3), core (0.2) |
| elliptical | quadriceps (0.6), cardiovascular (0.8) | glutes (0.5), hamstrings (0.4), calves (0.3), shoulders (0.2) |
| stair_climber | quadriceps (0.7), glutes (0.7), cardiovascular (0.8) | calves (0.5), hamstrings (0.4), core (0.2) |
| jump_rope | calves (0.9), cardiovascular (0.9) | quadriceps (0.4), forearms (0.3), shoulders (0.3), core (0.2) |
| ski | quadriceps (0.7), cardiovascular (0.8) | glutes (0.6), hamstrings (0.5), core (0.5), calves (0.4) |

### 5.2 Core Exercise Catalog — Seeder (~50 exercises, ships with app)

**Seeder:** `ExerciseSeeder`

Seed a curated catalog of the most common exercises with manually verified muscle load profiles. These are the foundation that the app ships with.

**Compound movements (15):**
Barbell Back Squat, Front Squat, Deadlift, Romanian Deadlift, Bench Press, Incline Bench Press, Overhead Press, Barbell Row, Pull-up, Chin-up, Dip, Lunge, Bulgarian Split Squat, Hip Thrust, Clean & Press

**Isolation movements (15):**
Bicep Curl, Hammer Curl, Tricep Extension, Tricep Pushdown, Lateral Raise, Front Raise, Rear Delt Fly, Leg Curl, Leg Extension, Calf Raise, Face Pull, Chest Fly, Cable Crossover, Preacher Curl, Wrist Curl

**Bodyweight / functional (15):**
Push-up, Plank, Side Plank, Mountain Climber, Burpee, Box Jump, Kettlebell Swing, Goblet Squat, Turkish Get-up, Hanging Leg Raise, Ab Wheel Rollout, Inverted Row, Pike Push-up, Glute Bridge, Dead Bug

**Cardio / conditioning (5):**
Battle Ropes, Sled Push, Sled Pull, Farmer's Walk, Bear Crawl

Each exercise must include its full set of `ExerciseMuscleLoad` entries. Example for Barbell Back Squat:

| Muscle | Role | Load Factor |
|---|---|---|
| quadriceps | primary | 1.0 |
| glutes | primary | 0.9 |
| hamstrings | secondary | 0.5 |
| lower_back | stabilizer | 0.3 |
| core | stabilizer | 0.3 |
| calves | stabilizer | 0.1 |

### 5.3 Expanded Exercise Catalog — AI-Assisted Artisan Command

For the long tail of exercises beyond the core 50, use an Artisan command that leverages the Claude API to generate muscle load profiles. This allows the catalog to scale without manually researching every exercise's muscle activation profile.

**Command:** `php artisan exercises:generate`

**Usage:**

```bash
# Generate a single exercise with interactive review
php artisan exercises:generate "Zercher Squat" --review

# Generate multiple exercises from a file
php artisan exercises:generate --from-file=exercises.txt --review

# Generate without review (for bulk import, use with caution)
php artisan exercises:generate "Cable Face Pull"

# Dry run — show what would be created without saving
php artisan exercises:generate "Zercher Squat" --dry-run
```

**Specification:**

```
Command: exercises:generate
Arguments:
  name        Exercise name (optional if --from-file is used)

Options:
  --from-file=    Path to a text file with one exercise name per line
  --review        Show generated profile and ask for confirmation before saving
  --dry-run       Show generated profile without saving
  --equipment=    Override equipment type (skip AI suggestion for this field)
```

**Implementation details:**

1. Accept exercise name(s) as input
2. Check if exercise already exists in database — skip if it does
3. Send a structured prompt to the Claude API (claude-sonnet-4-20250514 for cost efficiency)
4. The prompt must instruct Claude to return JSON only, with this exact structure:

```json
{
    "name": "Zercher Squat",
    "category": "compound",
    "equipment": "barbell",
    "movement_pattern": "squat",
    "primary_muscles": ["quadriceps", "glutes"],
    "secondary_muscles": ["core", "upper_back", "biceps"],
    "muscle_loads": [
        { "muscle_group": "quadriceps", "role": "primary", "load_factor": 0.95 },
        { "muscle_group": "glutes", "role": "primary", "load_factor": 0.8 },
        { "muscle_group": "core", "role": "secondary", "load_factor": 0.6 },
        { "muscle_group": "upper_back", "role": "secondary", "load_factor": 0.5 },
        { "muscle_group": "biceps", "role": "secondary", "load_factor": 0.5 },
        { "muscle_group": "hamstrings", "role": "secondary", "load_factor": 0.4 },
        { "muscle_group": "lower_back", "role": "stabilizer", "load_factor": 0.3 }
    ]
}
```

5. The system prompt must include:
    - The full list of valid enum values for MuscleGroup, ExerciseCategory, Equipment, MovementPattern, and MuscleRole
    - Guidelines for load factor assignment:
        - Primary movers: 0.7–1.0
        - Secondary movers: 0.3–0.6
        - Stabilizers: 0.1–0.3
    - Instruction to return ONLY valid JSON, no markdown fences, no preamble
6. Parse the response, validate all enum values against the PHP enums
7. If `--review` flag is set:
    - Display the generated profile in a formatted table
    - Ask: "Save this exercise? (y/n/e)" where `e` opens an editor to modify the JSON
    - Only persist if confirmed
8. If `--dry-run`, display and exit
9. Wrap persistence in a database transaction:
    - Create Exercise record
    - Create all ExerciseMuscleLoad records

**System prompt for the Claude API call:**

```
You are an exercise science expert. Given an exercise name, return its complete
muscle activation profile as JSON.

Rules:
- Return ONLY valid JSON. No markdown, no explanation, no preamble.
- Use ONLY these muscle groups: chest, upper_back, shoulders, biceps, triceps,
  forearms, core, lower_back, quadriceps, hamstrings, glutes, calves, hip_flexors,
  cardiovascular
- Use ONLY these categories: compound, isolation, cardio, mobility
- Use ONLY these equipment types: barbell, dumbbell, kettlebell, bodyweight,
  machine, cable, band, other
- Use ONLY these movement patterns: squat, hinge, push, pull, carry, rotation,
  core, other
- Use ONLY these roles: primary, secondary, stabilizer
- Load factor guidelines:
  - Primary movers (main target muscles): 0.7–1.0
  - Secondary movers (assist the movement): 0.3–0.6
  - Stabilizers (maintain posture/balance): 0.1–0.3
- Include ALL meaningfully activated muscles, even stabilizers
- Be accurate — these values drive recovery and training load calculations
```

**Error handling:**
- If the API returns invalid JSON, retry once with a stricter prompt
- If enum values don't match, log a warning and skip the invalid muscle entry
- If the exercise name is ambiguous (e.g. "curl"), ask the user to be more specific

### 5.4 Future: User-Submitted Exercises (via App UI)

For a later phase, expose the same AI-assisted flow in the application UI:

1. User enters an exercise name in the app
2. App calls an internal API endpoint that triggers the same Claude API prompt
3. User reviews the suggested profile and can adjust load factors via sliders
4. On confirmation, exercise is saved to the catalog (scoped to user or global pending admin review)

This is out of scope for the initial implementation but the Artisan command establishes the pattern.

---

## 6. MCP Tool Updates

### 6.1 New Tools

**`get-exercise-catalog-tool`**
- Parameters: `category` (optional), `equipment` (optional), `movement_pattern` (optional), `muscle_group` (optional)
- Returns: Filtered list of exercises with their muscle load profiles
- Purpose: Browse available exercises when building workouts

**`add-block-to-workout-tool`**
- Parameters: `workout_id`, `parent_id` (nullable), `type`, `position`, `label` (optional), `repeat_count` (default 1), `rest_between_repeats_seconds` (optional)
- Plus type-specific params depending on `type`:
    - interval: `duration_seconds`, `distance_meters`, `intensity`, `target_pace_seconds_per_km`, `target_heart_rate_zone`
    - exercise_group: `group_type`, `rounds`, `rest_between_rounds_seconds`
    - rest: `duration_seconds`
    - note: `content`
    - group: no additional params (container only)
- Validates nesting depth (max 3)
- Returns: Created block with ID

**`add-exercise-to-group-tool`**
- Parameters: `exercise_group_id`, `exercise_id`, `position`, `sets`, `reps` (optional), `duration_seconds` (optional), `weight_kg` (optional), `rpe_target` (optional), `rest_between_sets_seconds` (optional), `notes` (optional)
- Returns: Created exercise entry

**`remove-block-tool`**
- Parameters: `block_id`
- Cascades to children and blockable content
- Cannot remove blocks from completed workouts

**`reorder-blocks-tool`**
- Parameters: `block_ids` (array of block IDs in desired order)
- All blocks must share the same parent (or all be root-level blocks of the same workout)

**`get-workout-structure-tool`**
- Parameters: `workout_id`
- Returns: Full nested block tree with all content, exercises, and computed metadata

**`get-muscle-load-preview-tool`**
- Parameters: `workout_id`
- Returns: Per-muscle-group load breakdown for a planned workout (calculated on the fly using current load factors)
- Purpose: Preview the intensity distribution before completing a workout

**`get-recovery-status-tool`**
- Parameters: none (uses authenticated user)
- Returns: Per-muscle-group fatigue score, status (fresh/recovering/fatigued), and ready_for_heavy flag
- Reads from snapshots for fast response

**`suggest-target-muscles-tool`**
- Parameters: none
- Returns: Muscles sorted by readiness, with recommendations for heavy vs light training
- Combines recovery status with recent training frequency balance

### 6.2 Updated Tools

**`create-workout-tool`**
- Keep existing parameters
- The `notes` field remains for free-text workout descriptions
- Blocks are added separately via `add-block-to-workout-tool` after creation

**`complete-workout-tool`**
- Keep existing parameters (workout_id, rpe, feeling)
- Add side effect: trigger muscle load snapshot creation via WorkoutCompletionService

---

## 7. Implementation Order

### Phase 1: Schema & Models
1. Create all PHP enums
2. Create all migrations (in order listed above)
3. Create Eloquent models with relationships
4. Create validation rules (MaxBlockDepth, etc.)
5. Run migrations

### Phase 2: Exercise Catalog & Data Population
1. Create Exercise and ExerciseMuscleLoad models
2. Create ActivityMuscleLoad model
3. Create `ActivityMuscleLoadSeeder` with all activity profiles
4. Create `ExerciseSeeder` with the core ~50 exercises and their muscle load profiles
5. Run seeders
6. Implement `exercises:generate` Artisan command with Claude API integration
7. Test the command with `--dry-run` and `--review` flags
8. Use the command to generate an additional batch of exercises to validate quality

### Phase 3: Block System
1. Implement WorkoutBlock with polymorphic relations and self-referential nesting
2. Implement IntervalBlock, ExerciseGroup, RestBlock, NoteBlock models
3. Implement ExerciseEntry model
4. Add block tree loading to Workout model (eager load with `nestedChildren`)
5. Write tests for nesting depth validation (max 3 levels)
6. Write tests for block tree retrieval and ordering

### Phase 4: Muscle Load Calculation
1. Implement MuscleLoadSummary value object
2. Implement MuscleLoadCalculator service
3. Implement interval duration estimation helper (see section 4.4 below)
4. Write tests with various workout structures (pure strength, pure cardio, mixed, nested groups)

### Phase 5: Snapshots & Recovery
1. Create WorkoutMuscleLoadSnapshot model
2. Update WorkoutCompletionService to create snapshots on completion
3. Implement MuscleRecoveryService
4. Write tests for recovery decay calculations

### Phase 6: MCP Tools
1. Implement read tools first: `get-exercise-catalog-tool`, `get-workout-structure-tool`
2. Implement builder tools: `add-block-to-workout-tool`, `add-exercise-to-group-tool`
3. Implement management tools: `remove-block-tool`, `reorder-blocks-tool`
4. Implement intelligence tools: `get-muscle-load-preview-tool`, `get-recovery-status-tool`, `suggest-target-muscles-tool`
5. Update `complete-workout-tool` to trigger snapshots

### Phase 7: Testing & Refinement
1. Integration tests for full workout creation → completion → recovery flow
2. Edge cases: empty workouts, deeply nested blocks at max depth, exercises with missing load data
3. Performance testing for block tree queries with eager loading
4. Verify snapshot accuracy matches on-the-fly calculation

---

## 8. Key Design Decisions

| Decision | Choice | Rationale |
|---|---|---|
| Block content storage | Polymorphic (`blockable_type`/`blockable_id`) | Each block type has different fields; keeps tables clean and type-safe |
| Nesting | Self-referential `parent_id` on WorkoutBlock | Supports arbitrary nesting with simple depth cap |
| Max depth | 3 levels | Covers all real-world workout structures including nested group-in-group |
| Muscle load storage | Snapshots on completion | Fast recovery queries; load factor changes only affect future workouts |
| Cardio muscle mapping | `ActivityMuscleLoad` table | Same structure as exercise loads; supports adding new activities without code changes |
| Repeat logic | `repeat_count` on WorkoutBlock level | Any block or group of blocks can be repeated, including nested groups |
| Load factor changes | Forward-only | Changing a load factor in the exercise catalog does not retroactively update historical snapshots |
| Exercise catalog seeding | Core 50 via seeder, long tail via AI-assisted Artisan command | Manual curation for quality on the essentials, scalable AI-assisted generation for the rest |
| Interval duration for load calc | Target pace if set, else default pace per intensity | Allows accurate load calculation for distance-based intervals without requiring user calibration |
