# Permissions

Like Roles, Permissions are defined using PHP 8.1+ Enums.

## Defining Permissions on Roles

When you define an Enum for a Role, it includes a `permissions()` method. This method maps the role to specific permissions:

```php
    public function permissions(): array
    {
        return match ($this) {
            self::ADMINISTRATOR => [
                Permission::VIEW,
                Permission::CREATE,
                Permission::UPDATE,
                Permission::DELETE,
            ],

            default => [];
        };
    }
```

If a user is assigned the `ADMINISTRATOR` role, they will automatically inherit `VIEW`, `CREATE`, `UPDATE`, and `DELETE` permissions.

## Direct Permissions

You can also assign a permission directly to a user, bypassing roles.

```php
// Assign a permission
$user->assignPermission(Permission::CREATE);

// Or using addPermission
$user->addPermission(Permission::CREATE);

// Sync multiple permissions (replaces existing direct permissions)
$user->syncPermissions([Permission::CREATE, Permission::UPDATE]);
```

## Checking Permissions

Check if a user has a specific permission (either directly or through a role):
```php
$user->hasPermission(Permission::CREATE); // true or false

// For readability, you may also use:
$user->isAbleTo(Permission::CREATE);
```

Check if a user has ANY or ALL permissions:
```php
// Check if user has ANY of the given permissions
$user->hasAnyPermission([Permission::CREATE, Permission::DELETE]);

// Check if user has ALL of the given permissions
$user->hasAllPermissions([Permission::CREATE, Permission::DELETE]);
```

## Retrieving Permissions

You can fetch different sets of permissions for a user:

```php
// Get all permissions (both direct and through roles)
$user->permissions();

// Get only directly assigned permissions
$user->directPermissions();

// Get permissions that are inherited through assigned roles
$user->permissionsThroughRoles();
```

## Removing Direct Permissions

```php
$user->removePermission(Permission::CREATE);
```

Removing a permission invalidates the necessary caches automatically.
