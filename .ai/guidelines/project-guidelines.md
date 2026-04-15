# Project Guidelines

## Code Style

- Use string interpolation over concatenation.
- Structure conditionals: unhappy paths (guards, validation, errors) first; happy path last.

## Development

- Local auth shortcut: `GET /login/as/{user}` — logs in as any user, no credentials needed.

## Units of Measurement

Metric only. All values stored in base units:

| Measurement | Storage/Range | DB Type |
|---|---|---|
| Duration | Seconds | `integer` |
| Distance | Meters | `decimal(10, 2)` |
| Weight | Kilograms | `decimal(8, 2)` |
| Pace | Seconds per km | `integer` |
| Heart rate | Beats per minute | `integer` |
| Power | Watts | `integer` |
| RPE | 1–10 | `integer` |
| Feeling | 1–5 | `integer` |
| HR Zone | 1–5 | `integer` |

Converters in `App\Support\Workout`: `TimeConverter`, `DistanceConverter`, `PaceConverter`.

## Architecture

- Prefer value objects/DTOs over primitives for domain concepts.
- DTOs must be `readonly` classes. Use named static factory methods for derived values.
- Use backed enums (TitleCase keys) with behavior methods over string constants.
- Action classes: explicit params to `execute()`, no authorization, no service location.
- Wrap multi-step mutations in `DB::transaction()`.
- Authorize in the transport layer (controllers, Livewire components), never in actions.
- Livewire: authorize at action level (e.g. `saveReport`), not in `mount()`.
- Models must not use `app()` — extract logic to action classes.

## Database Safety

- **Never** run `migrate:fresh` or `migrate:reset` without explicit permission.
- Use `--env=testing` for destructive migration commands.

## Self-Improvement

- When you discover a recurring pattern not documented here, propose adding it (confirmed across multiple files only).
- Explain the proposed change before making it.
