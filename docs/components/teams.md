# Teams

The package supports polymorphic multi-team setups. This means you can assign a Role or Permission to a User within the context of a specific Team Model.

## Enabling Teams

First, make sure teams are enabled in your configuration (`config/permissions.php`):

```php
    "teams" => [
        "is_enabled" => true,
    ],
```

And configure your Teams for your Model:

```php
    "models" => [
            \App\Models\User::class=> [
                \App\Models\Team::class,
                \App\Models\Company::class,
            ],
    ],
```

Then run `php artisan permissions:install`.

## Assigning with a Team

Pass the Team model instance as the second parameter when assigning roles or permissions.

```php
$team = Team::find(1);

$user->assignRole(Role::ADMIN, $team);
$user->addRole(Role::ADMIN, $team);

$user->assignPermission(Permission::CREATE, $team);
$user->addPermission(Permission::CREATE, $team);
```

## Checking within a Team

When checking roles or permissions, you also pass the Team instance to check if the user has the ability specifically on that team.

```php
// With Teams Model
$user->hasRole(Role::ADMIN, $team); // true or false
$user->hasPermission(Permission::CREATE, $team); // true or false
```

Note: A role or permission assigned globally (without a team) **does not** automatically apply when checking for a specific team, and vice versa. They are isolated.

## Retrieving Team Assignments

You can retrieve roles or permissions specific to a team:

```php
$user->roles($team);
$user->permissions($team);
$user->directPermissions($team);
$user->permissionsThroughRoles($team);
```

### Retrieving Teams

If a user belongs to any teams via roles (or direct permissions mapped through roles), you can retrieve them:

```php
// Get all distinct teams the user is assigned a role in
$teams = $user->teams();
```
