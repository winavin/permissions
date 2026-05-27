# Roles

Roles are defined using PHP 8.1+ Enums. By keeping roles as Enums, you avoid storing the structure in your database, giving you version control over your roles.

## Assigning Roles

You can assign roles to a user globally:

```php
// Using the enum case
$user->assignRole(Role::ADMIN);

// Alternatively, you can use addRole, which is an alias for assignRole
$user->addRole(Role::ADMIN);
```

You can assign multiple roles at once by syncing:
```php
$user->syncRoles([Role::ADMIN, Role::MANAGER]);
```

## Checking Roles

Check if a user has a specific role:
```php
$user->hasRole(Role::ADMIN); // true or false
```

Check if a user has ANY of the given roles:
```php
$user->hasAnyRole([Role::ADMIN, Role::MANAGER]); // true or false
```

Check if a user has ALL of the given roles:
```php
$user->hasAllRoles([Role::ADMIN, Role::MANAGER]); // true or false
```

## Retrieving Roles

Get a collection of all roles currently assigned to a user:
```php
$user->roles();
```

## Removing Roles

You can remove a specific role:
```php
$user->removeRole(Role::ADMIN);
```

## Caching

Roles checks are very fast because they are cached.
Cache is automatically managed internally to maintain speed. Whenever you `assignRole`, `removeRole`, or `syncRoles`, caches are automatically invalidated.

You should always use the provided methods (```assignRole```, ```removeRole```, etc) to modify roles instead of manipulating database records manually.
