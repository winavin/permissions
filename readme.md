# Permissions

A lightweight, Enum-driven Roles & Permissions system for Laravel, designed to support multiple user models and
multi-team setups without database-stored role and permissions definitions.

Unlike existing solutions such
as [santigarcor/laratrust](https://github.com/santigarcor/laratrust), [spatie/laravel-permission](https://github.com/spatie/laravel-permission), [JosephSilber/bouncer](https://github.com/JosephSilber/bouncer),
which tightly couple roles and permissions to database entries or a single team model, this package offers a
developer-first, elegant approach using modern PHP 8.1+ Enums and polymorphic teams.

## Features

- ✅ Roles and Permissions are defined through PHP Enums — no database storage for definitions.
- ✅ Built-in team support with team_type and team_id fields.
- ✅ Automatically publishes Enum, Model, and Migration files specific to each model.
- ✅ Caching for extremely fast role and permission checks.
- ✅ Caching for fast permission and role checks.
- ✅ Fully supports multiple user models (e.g., User, Admin, etc.).
- ✅ Optional --path support for organized Enums and Models

## How It Works

- You define Roles and Permissions as PHP Enums.
- Only assignments (which user has which role or permission) are stored in the database.
- Each Role Enum contains a permissions() method to map its permissions.
- Cache is automatically managed for faster lookups.
- For each model, two tables are created:
    - ```model_roles``` e.g. ```user_roles```, ```admin_roles```
    - ```model_permissions``` e.g. ```user_permissions```, ```admin_permissions```

  Replace model_ with the lowercase version of your model's name.

## Extending

Since the Enum and Model classes are published directly into your App namespace, you are free to extend them according
to your needs.

Examples:

- Add methods like ```description()```, ```label()```, ```icon()``` inside your Enums.
- Add fields like ```expired_at``` to your role/permission tables to implement auto-expiry logic.
    - Set up scheduled jobs to auto-remove expired roles or permissions. (It is recommended to use
      ```$user->removePermission($permission)``` or ```$user->removeRole($role)``` methods for removals, which
      automatically clear cache.)

This approach gives you full control while maintaining the package's performance and simplicity.

## Installation

Install the package using Composer:

```bash
composer require winavin/permissions
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="permissions.config"
```

## Setup

### 1. Publish Configuration
```bash
php artisan vendor:publish --tag="permissions.config"
```

### 2. Configure your models and teams in the config file
```php
    "teams" => [
        "is_enabled" => true,
    ],

    "models" => [
            \App\Models\User::class=> [
                // \App\Models\Team1::class,
                // \App\Models\Team2::class,
            ]   ,
            // \App\Models\Admin::class=> [
            //     \App\Models\Team3::class,
            //     \App\Models\Team4::class,
            // ],
        ],
```

### 3. Now run install coammand to create the necessary tables and models:

```bash
php artisan permissions:install
````

This will create the necessary database migrations, Pivot models, and enum classes for your user models and teams.



## Alternate Option
### 1. Publish Migration & Model Classes

Use the following command to generate the database migration and model scaffolding for a user model:

```bash
php artisan permissions:make-model
OR
php artisan permissions:make-model {User Model} --path={Team}
```

Replaces {User Model} with your model prefix (e.g. User, Customer, Employee) whichever you want to assign roles
and permissions.

This will publish following files

- ```create_{prefix}_roles_permissions_tables.php``` migration file
- ```{path}/{Prefix}Roles.php``` Model for Roles entry
- ```{path}/{Prefix}Permissions.php``` Model for Permissions Entry

### 2. Add Trait to User Models

Add the ```HasRolesAndPermissions``` trait to your model:

```php
use Winavin\Permissions\Traits\HasRolesAndPermissions;

class User extends Authenticatable
{
    use HasRolesAndPermissions;
```

### 3. Publish Enum Classes for Team

Use this command to create role and permission Enums for your teams.:

```bash
php artisan per:make-enum {Team}
OR
php artisan per:make-enum {Team} --path=Admin
```

This will publish

- ```{path}/{Prefix}Role.php``` // Enum for Roles
- ```{path}/{Prefix}Permission.php``` // Enum for Permissions

### 4. Define Permissions

You can create new Roles and Permissions cases in Enum.
After that define permissions for Role using the permissions() method inside the corresponding Role Enum.

```php
    public function permissions(): array
    {
        return match ($this) {
            self::ADMINISTRATOR => [
                TeamNamePermission::VIEW,
                TeamNamePermission::CREATE,
                TeamNamePermission::UPDATE,
                TeamNamePermission::DELETE,
            ],

            default => [];
        };
    }
```

## Usage

### Assigning Roles and Permissions

```php
// Without Teams Model
$user->assignRole($role);
//OR
$user->addRole($role);
//////////////
$user->assignPermission($permission);
// OR
$user->addPermission($permission);

// With Teams Model
$user->assignRole($role, $team);
$user->addRole($role, $team);
$user->assignPermission($permission, $team);
$user->addPermission($permission, $team);
```

### Checking Roles and Permissions

```php
// Without Teams Model
$user->hasRole($role); // true or false
$user->hasPermission($permission); // true or false

// With Teams Model
$user->hasRole($role, $team); // true or false
$user->hasPermission($permission, $team); // true or false
```

For readability, you may also use:

```php
$user->isAbleTo($permission);
```

You can also check for multiple roles or permissions:

```php
// Check if user has ANY of the given roles
$user->hasAnyRole([$role1, $role2]);
$user->hasAnyRole([$role1, $role2], $team);

// Check if user has ALL of the given roles
$user->hasAllRoles([$role1, $role2]);
$user->hasAllRoles([$role1, $role2], $team);

// Check if user has ANY of the given permissions
$user->hasAnyPermission([$permission1, $permission2]);
$user->hasAnyPermission([$permission1, $permission2], $team);

// Check if user has ALL of the given permissions
$user->hasAllPermissions([$permission1, $permission2]);
$user->hasAllPermissions([$permission1, $permission2], $team);
```

### Retrieving Roles and Permissions

You can retrieve a user's assigned roles and permissions. These methods return a Laravel `Collection` and accept an optional `$team` parameter to limit results to a specific team:

```php
// Get all roles assigned to the user
$user->roles(); 
$user->roles($team);

// Get all permissions (both direct and through roles)
$user->permissions();
$user->permissions($team);

// Get only directly assigned permissions
$user->directPermissions();
$user->directPermissions($team);

// Get permissions that are inherited through assigned roles
$user->permissionsThroughRoles();
$user->permissionsThroughRoles($team);
```

### Retrieving Teams

If a user belongs to any teams via roles (or direct permissions mapped through roles), you can retrieve them:

```php
// Get all distinct teams the user is assigned a role in
$user->teams();
```


### Syncing Roles and Permissions

You can sync roles or permissions to replace the current ones:

```php
// Without Teams Model
$user->syncRoles($rolesArray);
$user->syncPermissions($permissionsArray);

// With Teams Model
$user->syncRoles($rolesArray, $team);
$user->syncPermissions($permissionsArray, $team);
```

This will remove all old roles/permissions and assign the new ones.

### Removing Roles and Permissions

You can remove a role or permission individually:

```php
$user->removeRole($role);
$user->removeRole($role, $team);
$user->removePermission($permission);
$user->removePermission($permission, $team);
```

All related cache keys will be automatically cleared when you remove a role or permission.

## Overwriting (Optional)

You can overwrite following methods in trait as per your need.

- ```protected function getRoleEnum($team = null): string```
- ```protected function getPermissionEnum($team = null): string```
- ```protected function getRoleClass($team = null): string```
- ```protected function getPermissionClass($team = null): string```

## Notes

- Cache is automatically managed internally to maintain speed.
- Whenever you assign, remove, or sync roles/permissions, caches are automatically invalidated.
- You should always use the provided methods (```assignRole```, ```addRole```, ```assignPermission```,
  ```addPermission```, ```removeRole```, ```removePermission```) to modify roles and permissions instead of manipulating
  database records manually.

## Contributing
If you want to contribute to this package, feel free to submit a pull request or open an issue on GitHub.

## License

This package is open-sourced software licensed under the MIT.
