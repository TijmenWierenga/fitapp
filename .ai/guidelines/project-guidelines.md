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
