# Forjix ORM

Active Record ORM for the Forjix framework with relationships support.

## Installation

```bash
composer require forjix/orm
```

## Defining Models

```php
use Forjix\Orm\Model;

class User extends Model
{
    protected string $table = 'users';

    protected array $fillable = ['name', 'email'];

    protected array $hidden = ['password'];
}
```

## Basic Operations

```php
// Create
$user = User::create([
    'name' => 'John',
    'email' => 'john@example.com',
]);

// Find
$user = User::find(1);
$user = User::findOrFail(1);

// Query
$users = User::where('active', true)
    ->orderBy('name')
    ->get();

// Update
$user->name = 'Jane';
$user->save();

// Delete
$user->delete();
```

## Relationships

### Has One

```php
class User extends Model
{
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }
}
```

### Has Many

```php
class User extends Model
{
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
```

### Belongs To

```php
class Post extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

### Belongs To Many

```php
class User extends Model
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
```

## Eager Loading

```php
$users = User::with('posts', 'profile')->get();
```

## License

GPL-3.0
