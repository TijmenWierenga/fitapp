# Project Guidelines

## Code Style

### Closures
Prefer short closures (arrow functions) when possible. Always add explicit parameter types and return types.

```php
// Good - short closure preferred
$users->filter(fn (User $user): bool => $user->isActive());

// Acceptable - regular closure when variable assignment needed
$users->map(function (User $user): array {
    $permissions = $user->getAllPermissions();
    return [...$user->toArray(), 'permissions' => $permissions];
});

// Bad - no types
$users->filter(fn ($user) => $user->isActive());
```

### String Handling
Use string interpolation over concatenation.

```php
// Good
$message = "Hello {$user->name}, welcome!";

// Bad
$message = 'Hello ' . $user->name . ', welcome!';
```

## Control Flow

### Happy Path Last
Structure conditionals with unhappy paths (guards, validation, error cases) first, followed by the happy path.

```php
// Good
public function process(Order $order): void
{
    if (! $order->isPaid()) {
        throw new UnpaidOrderException();
    }

    if ($order->isExpired()) {
        return;
    }

    // Happy path logic
    $order->fulfill();
}

// Bad
public function process(Order $order): void
{
    if ($order->isPaid() && ! $order->isExpired()) {
        // Happy path logic
        $order->fulfill();
    }
}
```

## Laravel Best Practices

### Collections Over Arrays
Prefer Laravel Collections over raw arrays for data manipulation.

```php
// Good
return collect($items)
    ->filter(fn (Item $item): bool => $item->isActive())
    ->map(fn (Item $item): array => $item->toArray());

// Bad
$result = [];
foreach ($items as $item) {
    if ($item->isActive()) {
        $result[] = $item->toArray();
    }
}
return $result;
```

### Model Attributes
Use Laravel's `Attribute` class for computed properties instead of `get` methods. Always add generic type hints in doc-blocks.

```php
// Good
/**
 * @return Attribute<string, never>
 */
public function fullName(): Attribute
{
    return Attribute::make(
        get: fn (): string => "{$this->first_name} {$this->last_name}",
    );
}

// Bad
public function getFullNameAttribute(): string
{
    return "{$this->first_name} {$this->last_name}";
}
```

### Authorization
Use Laravel policies for authorization logic instead of inline checks.

```php
// Good
Gate::authorize('update', $post);

// Bad
if (auth()->user()->id !== $post->user_id) {
    abort(403);
}
```

### Authorization in Livewire
Use `$this->authorize()` with policies in Livewire action methods. Use `#[CurrentUser]` for injecting the authenticated user instead of the `Auth` facade.

```php
// Good
use Illuminate\Container\Attributes\CurrentUser;

public function saveReport(#[CurrentUser] User $user): void
{
    $this->authorize('create', [InjuryReport::class, $this->injury]);
    // ...
}

// Bad
public function saveReport(): void
{
    if (Auth::id() !== $this->injury->user_id) {
        return;
    }
    // ...
}
```

### Livewire Mount Methods
Omit `mount()` when it only assigns typed public properties — Livewire handles this automatically.

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

### No Service Location in Models
Eloquent models must not use the `app()` helper. Extract business logic to dedicated action classes instead.

```php
// Good - extract to action class
class NotifyUser
{
    public function __construct(
        private NotificationService $service,
    ) {}

    public function execute(User $user): void
    {
        $this->service->send($user);
    }
}

// Bad - using app() in model
class User extends Model
{
    public function notify(): void
    {
        app(NotificationService::class)->send($this);
    }
}
```

### Livewire Authorization Placement
Livewire components should authorize at the action level (e.g. `saveReport`, `deleteReport`), not in `mount()`. This keeps authorization close to the mutation and leverages policies consistently.

### Action Classes

Use action classes to encapsulate business logic that's reusable across transport layers.

```php
// Good - pure business logic, transport-agnostic
class CreateWorkoutPlan
{
    public function execute(User $user, WorkoutType $type, CarbonImmutable $startDate): WorkoutPlan
    {
        $plan = WorkoutPlan::create([
            'user_id' => $user->id,
            'type' => $type,
            'start_date' => $startDate,
        ]);

        $user->notify(new WorkoutPlanCreated($plan));

        return $plan;
    }
}

// Bad - coupled to transport layer
class CreateWorkoutPlan
{
    public function execute(): WorkoutPlan
    {
        // Don't access request/session/auth directly
        $user = auth()->user();

        // Don't perform authorization
        if (! $user->can('create', WorkoutPlan::class)) {
            abort(403);
        }

        $type = request()->input('type');

        return WorkoutPlan::create([...]);
    }
}
```

**Rules:**
- Accept all context as explicit parameters to `execute()` - no hidden dependencies on request, session, or auth
- Never perform authorization - that belongs in the transport layer (controller, Livewire component, command)
- Should be executable from any context: Livewire views, controllers, CLI commands, Tinker, or queued jobs

## Guideline Self-Improvement

When you discover a recurring pattern, convention, or gotcha that isn't documented here, propose adding it to the relevant section in `.ai/guidelines/project-guidelines.md`. Only propose additions that are confirmed across multiple files or interactions — not one-off observations. Always explain the proposed change before making it so the user can approve.

## Database Safety

- **Never** run `migrate:fresh` or `migrate:reset` without explicit permission — it destroys local data.
- Use `--env=testing` for destructive migration commands: `php artisan migrate:fresh --env=testing`
