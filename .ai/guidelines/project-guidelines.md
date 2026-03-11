# Project Guidelines

## Code Style

- Use string interpolation over concatenation.
- Structure conditionals with unhappy paths (guards, validation, error cases) first; happy path last.

## Local Development

### Quick Authentication

In the local environment, a route is available to log in as any user without credentials: `GET /login/as/{user}`.
Use this route when you need to bypass authentication for browser-based testing instead of going through the login form.

## Units of Measurement

This application uses **metric units exclusively**. No imperial units are supported.

### Storage Units

All values are stored in their base unit in the database:

| Measurement | Storage Unit | DB Column Type |
|---|---|---|
| Duration / time | Seconds | `integer` |
| Distance | Meters | `decimal(10, 2)` |
| Weight | Kilograms | `decimal(8, 2)` |
| Pace | Seconds per km | `integer` |
| Heart rate | Beats per minute (bpm) | `integer` |
| Power | Watts | `integer` |

### Converters

Use the converter classes in `App\Support\Workout` when converting between storage and display formats:

- `TimeConverter` — converts between seconds and hours/minutes/seconds
- `DistanceConverter` — converts between kilometers and meters
- `PaceConverter` — converts between seconds-per-km and minutes:seconds format

### Subjective Scales

| Scale | Range | Usage |
|---|---|---|
| RPE (Rate of Perceived Exertion) | 1–10 | Exercise-level and workout-level effort |
| Feeling | 1–5 | Post-workout subjective feeling |
| Heart rate zone | 1–5 | Cardio training zones |

## Architecture Constraints

### Value Objects Over Primitives

Prefer rich domain objects and value objects over primitive types (arrays, floats, strings) for domain concepts. Value objects make intent explicit, centralize behavior, and are easier to test.

### Enums Over String Constants

When a domain concept has a fixed set of values with associated behavior (labels, colors, formatting), use a backed enum instead of string constants. Derive related attributes as methods on the enum.

### Factory Methods on DTOs

When constructing a DTO requires computing derived values, use a named static factory method (e.g. `fromLoad()`) instead of putting logic in the constructor. The constructor should accept all values explicitly; the factory method computes and passes them.

### No Service Location in Models

Eloquent models must not use the `app()` helper. Extract business logic to dedicated action classes instead.

### Livewire Authorization Placement

Livewire components should authorize at the action level (e.g. `saveReport`, `deleteReport`), not in `mount()`. This keeps authorization close to the mutation and leverages policies consistently.

### Action Classes

Use action classes to encapsulate business logic that's reusable across transport layers.

- Accept all context as explicit parameters to `execute()` — no hidden dependencies on request, session, or auth
- Never perform authorization — that belongs in the transport layer (controller, Livewire component, command)
- Should be executable from any context: Livewire views, controllers, CLI commands, Tinker, or queued jobs

## Guideline Self-Improvement

When you discover a recurring pattern, convention, or gotcha that isn't documented here, propose adding it to the relevant section in `.ai/guidelines/project-guidelines.md`. Only propose additions that are confirmed across multiple files or interactions — not one-off observations. Always explain the proposed change before making it so the user can approve.

## Database Safety

- **Never** run `migrate:fresh` or `migrate:reset` without explicit permission — it destroys local data.
- Use `--env=testing` for destructive migration commands: `php artisan migrate:fresh --env=testing`
