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
