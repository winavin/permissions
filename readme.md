# Permissions

A lightweight, Enum-driven Roles & Permissions system for Laravel, designed to support multiple user models and multi-team setups without database-stored role and permissions definitions.

Unlike existing solutions such as [santigarcor/laratrust](https://github.com/santigarcor/laratrust), [spatie/laravel-permission](https://github.com/spatie/laravel-permission), [JosephSilber/bouncer](https://github.com/JosephSilber/bouncer), which tightly couple roles and permissions to database entries or a single team model, this package offers a developer-first, elegant approach using modern PHP 8.1+ Enums and polymorphic teams.

## Features
- ✅ Roles and Permissions are defined through PHP Enums — no database storage for definitions.
- ✅ Built-in team support with team_type and team_id fields.
- ✅ Automatically publishes Enum, Model, and Migration files specific to each model.
- ✅ Caching for extremely fast role and permission checks.
- ✅ Caching for fast permission and role checks.
- ✅ Fully supports multiple user models (e.g., User, Admin, etc.).

## How It Works
- You define Roles and Permissions as PHP Enums.
- Only assignments (which user has which role or permission) are stored in the database.
- Role Enums contain a permissions() method that defines which permissions are granted by that role.
- Cache is automatically managed for faster lookups.
- For each model, two tables are created:
    - ```model_roles``` e.g. ```user_roles```
    - ```model_permissions``` e.g. ```user_permissions```

    Replace model_ with the lowercase version of your model's name.

## Extending
Since the Enum and Model classes are published directly into your App namespace, you are free to extend them according to your needs.

Examples:
- Add methods like ```description()```, ```label()```, ```icon()``` inside your Enums.
- Add fields like ```expired_at``` to your role/permission tables to implement auto-expiry logic.
    - Set up scheduled jobs to auto-remove expired roles or permissions. (It is recommended to use ```$user->removePermission($permission)``` or ```$user->removeRole($role)``` methods for removals, which automatically clear cache.)

This approach gives you full control while maintaining the package's performance and simplicity.

## Installation

Install the package using Composer:

```bash
composer require winavin/permissions
```

Optionally, publish the configuration file:
```bash
php artisan vendor:publish --tag="permissions.config"
```

Next, install the permissions setup for your specific model:
```bash
php artisan permissions:install {ModelName}
```
Replace {ModelName} with the name of the Eloquent Model you want to assign roles and permissions to.

### Example
```bash
php artisan permissions:install User
```
This will generate:

Migration file
 - create_user_roles_permissions_tables.php

Enum files:
- App\Enums\UserRole
- App\Enums\UserPermission

Model files:
- App\Models\UserRoles
- App\Models\UserPermissions

## Setup

Add the ```HasRolesAndPermissions``` trait to your model:
```php
use Winavin\Permissions\Traits\HasRolesAndPermissions;

class User extends Authenticatable
{
    use HasRolesAndPermissions;
```

After publishing, you can immediately start editing Role and Permission cases to the Enum classes.

You define permissions for each Role using the permissions() method inside the corresponding Role Enum.

## Usage

### Assigning Roles and Permissions

```php
// Without Teams Model
$user->assignRole($role);
$user->addRole($role);
$user->assignPermission($permission);
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

## Notes
- Cache is automatically managed internally to maintain speed.
- Whenever you assign, remove, or sync roles/permissions, caches are automatically invalidated.
- You should always use the provided methods (```assignRole```, ```addRole```, ```assignPermission```, ```addPermission```, ```removeRole```, ```removePermission```) to modify roles and permissions instead of manipulating database records manually.

## License

This package is open-sourced software licensed under the [license file](MIT License).
