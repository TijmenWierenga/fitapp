# Traiq Workout Structure - Technical Specification

**Version:** 1.3  
**Date:** 2026-02-07  
**Purpose:** Universal workout builder supporting all sports and training modalities

---

## Overview

Workouts consist of three hierarchical levels:
- **Workout** → **Sections** → **Blocks** → **Exercises**

Each level can have notes. Structure is flat (no nesting), with fixed sequential order.

---

## 1. Workout Level

**Properties:**
- `name`: string (required) - e.g., "Monday Upper Body", "5K Tempo Run"
- `activity`: string (required) - e.g., "strength", "run", "bike", "pool_swim", "hike", "yoga", "hyrox"
- `scheduled_at`: datetime (required) - when the workout is scheduled
- `notes`: text (optional) - overall workout description, goals, coach notes

**Relationships:**
- Has many sections (ordered)

---

## 2. Section Level

Logical divisions of a workout (warm-up, main work, cooldown, etc.)

**Properties:**
- `name`: string (required) - Common: "warm-up", "main", "cooldown" | Custom: any user/LLM-provided string
- `order`: integer (required) - sequential position in workout
- `notes`: text (optional) - section-specific coaching cues

**Relationships:**
- Belongs to workout
- Has many blocks (ordered)

---

## 3. Block Level

Defines HOW exercises are performed (sets, circuits, intervals, etc.)

**Properties:**

### Required
- `block_type`: string (required) - one of:
  - `straight_sets` - traditional sets (all sets of exercise A, then exercise B)
  - `circuit` - rotate through 3+ exercises sequentially
  - `superset` - alternate between 2 exercises
  - `interval` - work/rest timing structure
  - `amrap` - as many rounds as possible in time cap
  - `for_time` - complete prescribed work as fast as possible
  - `emom` - every minute on the minute
  - `distance_duration` - single continuous effort
  - `rest` - simple rest/recovery block

- `order`: integer (required) - sequential position in section

### Optional Structure
- `rounds`: integer (optional) - how many times to cycle through exercises in this block

### Optional Timing (all in seconds)
- `rest_between_exercises`: integer (optional) - rest within a round
- `rest_between_rounds`: integer (optional) - rest between full cycles
- `time_cap`: integer (optional) - for AMRAP, For Time blocks
- `work_interval`: integer (optional) - for interval blocks
- `rest_interval`: integer (optional) - for interval blocks

### Notes
- `notes`: text (optional) - block-specific coaching cues, pacing strategy

**Relationships:**
- Belongs to section
- Has many exercises (ordered)

---

## 4. Exercise Level

Individual movements/activities within a block.

**Properties:**

### Required
- `name`: string (required) - e.g., "Barbell Bench Press", "Easy Jog", "Plank Hold"
- `order`: integer (required) - sequential position in block

### Optional Volume/Load
- `target_sets`: integer (optional)
- `target_reps_min`: integer (optional)
- `target_reps_max`: integer (required if target_reps_min is set)
- `target_weight`: decimal (optional) - in kg
- `target_duration`: integer (optional) - in seconds
- `target_distance`: decimal (optional) - in meters

### Optional Intensity
- `target_rpe`: decimal (optional) - 1.0 to 10.0 scale
- `target_tempo`: string (optional) - e.g., "3-1-1-0" (eccentric-pause-concentric-pause)
- `target_heart_rate_zone`: integer (optional) - 1 to 5 (fixed zones)
- `target_heart_rate_min`: integer (optional) - in bpm
- `target_heart_rate_max`: integer (required if target_heart_rate_min is set) - in bpm
- `target_pace_min`: integer (optional) - in seconds per km
- `target_pace_max`: integer (required if target_pace_min is set) - in seconds per km
- `target_power`: integer (optional) - in watts

### Optional Timing
- `rest_after`: integer (optional) - in seconds, rest after this exercise before next

### Notes
- `notes`: text (optional) - form cues, modifications, substitutions

**Relationships:**
- Belongs to block

---

## Data Type Standards

**All units in metric (Euro standard):**
- Weight: kg (decimal)
- Distance: meters (decimal) - display as km when ≥ 1000m
- Duration/Time: seconds (integer) - display via existing time converter helper
- Pace: seconds per km (integer) - display as MM:SS/km via time converter
- Heart Rate: bpm (integer)
- Power: watts (integer)
- RPE: 1.0-10.0 (decimal, one decimal place)

**Range Rules:**
- Reps: Can have only `target_reps_max` (fixed) OR both `target_reps_min` + `target_reps_max` (range)
- Heart Rate: Must have BOTH `target_heart_rate_min` + `target_heart_rate_max` if used
- Pace: Must have BOTH `target_pace_min` + `target_pace_max` if used

---

## Block Type Behaviors

### straight_sets
- Exercises performed sequentially
- Complete all sets of exercise A before moving to exercise B
- Uses: `rounds` (as sets), `rest_between_exercises`

### circuit
- Rotate through all exercises, then repeat
- Uses: `rounds`, `rest_between_exercises`, `rest_between_rounds`

### superset
- Alternate between 2 exercises (special case of circuit)
- Uses: `rounds`, `rest_between_exercises`, `rest_between_rounds`

### interval
- Structured work/rest periods
- Uses: `rounds`, `work_interval`, `rest_interval`
- Exercise duration often defined by `work_interval` rather than exercise-level `target_duration`

### amrap
- Complete as many rounds as possible within time limit
- Uses: `time_cap`
- `rounds` not used (user determines how many they complete)

### for_time
- Complete prescribed work as fast as possible
- Uses: `time_cap` (optional max time)
- Exercises define work (reps/distance), user completes ASAP

### emom
- Start new exercise/set at each minute mark
- Uses: `rounds` (number of minutes)
- Exercise duration should fit within 60 seconds

### distance_duration
- Single continuous effort
- Usually one exercise per block
- Uses exercise-level `target_distance` or `target_duration`

### rest
- Simple recovery block
- May have one "exercise" like "Walk" or "Stretch"
- Uses exercise-level `target_duration`

---

## Design Principles

1. **All metrics optional** - only fill what's relevant for that exercise type
2. **Fixed order** - sections, blocks, exercises execute sequentially (no conditionals, no branching)
3. **No nesting** - flat structure, blocks cannot contain other blocks
4. **Consistent units** - metric system throughout, no user preferences
5. **Simple inheritance** - exercises inherit behavior from their block type
6. **Notes at every level** - coaching cues can be added to workout, section, block, or exercise

---

## MVP Scope

**In Scope:**
- Full CRUD for workouts, sections, blocks, exercises
- All block types listed above
- All target metrics as specified
- Notes at all levels
- LLM/user workout generation via MCP

**Out of Scope (Future):**
- Completion tracking (actual vs planned metrics)
- Nested blocks
- Conditional/optional blocks
- Partner/team workouts
- Exercise auto-substitution based on equipment/injury
- Progressive rep schemes (changing per round)
- LLM chat interface in frontend

**Assumption:**
- Users complete workouts as planned
- Modifications handled via LLM chat through MCP or manual editing in Livewire interface
- No real-time logging during workout (MVP)

---

## Garmin FIT Export Compatibility

**Fully Compatible:**
- Running, cycling, swimming, rowing workouts
- Pace, heart rate, power, distance targets transfer completely

**Partially Compatible:**
- Strength training: structure and timing transfer, weight/reps/RPE do not

**Not Compatible:**
- AMRAP, For Time workouts (no task completion in FIT)
- Weight, reps, tempo, RPE metadata

**Export Strategy:**
- Cardio workouts: full export
- Strength workouts: structure-only export
- Advanced conditioning: Traiq-only (no export)

---

## MCP Server Interface

### Primary Creation Interface
The **MCP server is the primary interface** for workout creation and modification. Users interact via natural language chat through Claude Desktop.

### Required MCP Tools

**Workout CRUD:**
- `create-workout-tool` - Create workout with full nested structure (sections, blocks, exercises)
- `get-workout-tool` - Retrieve complete workout with all relationships
- `update-workout-tool` - Modify existing workout (any level: workout, section, block, exercise)
- `delete-workout-tool` - Remove workout
- `list-workouts-tool` - Already exists, may need enhancement for filtering

**Additional Tools (Nice to Have):**
- `duplicate-workout-tool` - Clone existing workout with new schedule
- `generate-workout-tool` - High-level tool that uses fitness profile + injury data to create appropriate workout

### Tool Design Considerations

**Nested Structure in Single Call:**
The `create-workout-tool` should accept the full workout structure in one call:
```json
{
  "name": "Monday Upper Body",
  "activity": "strength",
  "scheduled_at": "2026-02-10 18:00:00",
  "notes": "Focus on progressive overload",
  "sections": [
    {
      "name": "warm-up",
      "order": 1,
      "blocks": [
        {
          "block_type": "straight_sets",
          "order": 1,
          "exercises": [
            {
              "name": "Arm circles",
              "order": 1,
              "target_reps_max": 10,
              "target_duration": 30
            }
          ]
        }
      ]
    }
  ]
}
```

**Why Single Call vs Multiple:**
- LLM can generate entire workout structure at once
- Reduces round-trips and complexity
- Easier validation (check full structure before commit)
- Matches how users think ("create me a workout" not "create workout, add section, add block...")

**Update Tool Flexibility:**
The `update-workout-tool` should support:
- Updating workout-level fields (name, date, notes)
- Adding/removing/reordering sections
- Adding/removing/reordering blocks within sections
- Adding/removing/reordering exercises within blocks
- Modifying individual exercise metrics

**Return Full Structure:**
All tools should return the complete workout structure (with relationships) after operations, so the LLM can confirm changes to the user.

---

## Livewire Frontend Interface (Future)

### Component Requirements

**Primary Components Needed:**
- `WorkoutCalendar` - Calendar view with scheduled workouts
- `WorkoutDetail` - Read-only workout display with collapsible sections/blocks
- `WorkoutEditor` - Full edit mode with inline editing and drag-and-drop reordering

### Data Requirements

**Workout List/Calendar:**
- Display workouts on scheduled dates
- Color coding by activity type
- Click to view/edit
- Drag-and-drop to reschedule
- Filter by activity type, upcoming/past

**Workout Detail (Read-Only):**
- Visual hierarchy: Workout → Sections (collapsible) → Blocks → Exercises
- Display formatted metrics using existing time converter helper
- Show block type badges
- Expand/collapse sections/blocks
- Edit, Duplicate, Delete actions

**Workout Editor:**
- Inline editing of all fields (name, activity, date, notes at all levels)
- Add/remove sections, blocks, exercises
- Drag-and-drop reordering (sections within workout, blocks within section, exercises within block)
- Dynamic form fields based on exercise type (show relevant metrics only)
- Validation with inline error messages
- Auto-save or explicit save

### Display Formatting Requirements

**Computed Properties Needed:**
- `estimated_duration` - Total workout time calculated from exercises and rest periods
- `total_exercises` - Count of exercises across all blocks
- `formatted_metrics` - Human-readable exercise metrics (e.g., "3×8-10 @ 80kg, Rest: 2:00")

**Time Display:**
- Use existing time converter helper for all duration/pace fields
- Store raw seconds in database
- Format for display only

### Validation Requirements

**Form Validation Rules:**
- Required fields: workout name, activity, scheduled_at, section name, block type, exercise name
- Range validation: min < max for reps, heart rate, pace
- Conditional requirements: max required if min is set
- Positive numbers only for sets, reps, weight, duration, etc.
- Inline error messages near problematic fields

### Database Indexes

For performance on common queries:
- `workouts.scheduled_at` - Filter upcoming/past workouts
- `workouts.activity` - Filter by activity type
- `sections.workout_id, sections.order` - Fetch ordered sections
- `blocks.section_id, blocks.order` - Fetch ordered blocks
- `exercises.block_id, exercises.order` - Fetch ordered exercises

### Cascade Deletes

Configure relationships to cascade:
- Delete workout → cascade delete sections → blocks → exercises
- Prevents orphaned records

---

## Example: Complete Workout Structure

```
Workout: "Hyrox Race Simulation"
├─ Section: "warm-up"
│  └─ Block: straight_sets
│     ├─ Exercise: "Light jog" (5 min)
│     └─ Exercise: "Dynamic stretches" (3 min)
│
├─ Section: "main"
│  ├─ Block: distance_duration
│  │  └─ Exercise: "Run" (1000m)
│  │
│  ├─ Block: for_time
│  │  └─ Exercise: "SkiErg" (1000m)
│  │
│  ├─ Block: distance_duration
│  │  └─ Exercise: "Run" (1000m)
│  │
│  └─ Block: for_time
│     └─ Exercise: "Sled push" (50m)
│
└─ Section: "cooldown"
   └─ Block: straight_sets
      ├─ Exercise: "Walk" (5 min)
      └─ Exercise: "Stretching" (10 min)
```

---

## Database Relationships Summary

```
workouts (1) ──→ (many) sections
sections (1) ──→ (many) blocks
blocks (1) ──→ (many) exercises
```

All relationships are one-to-many, ordered by `order` field.

---

**End of Specification**
