# Permissions for Laravel

A lightweight, Enum-driven Roles & Permissions system for Laravel, designed to support multiple user models and multi-team setups without database-stored role and permissions definitions.

Unlike existing solutions such as `santigarcor/laratrust`, `spatie/laravel-permission`, and `JosephSilber/bouncer`, which tightly couple roles and permissions to database entries or a single team model, this package offers a developer-first, elegant approach using modern PHP 8.1+ Enums and polymorphic teams.

## Features

- ✅ Roles and Permissions are defined through PHP Enums — no database storage for definitions.
- ✅ Built-in team support with `team_type` and `team_id` fields.
- ✅ Automatically publishes Enum, Model, and Migration files specific to each model.
- ✅ Caching for extremely fast role and permission checks.
- ✅ Fully supports multiple user models (e.g., User, Admin, etc.).
- ✅ Optional `--path` support for organized Enums and Models.

## How It Works

- You define Roles and Permissions as PHP Enums.
- Only assignments (which user has which role or permission) are stored in the database.
- Each Role Enum contains a `permissions()` method to map its permissions.
- Cache is automatically managed for faster lookups.
- For each model, two tables are created (e.g. `user_roles`, `user_permissions`).

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

1. **Publish Configuration**
```bash
php artisan vendor:publish --tag="permissions.config"
```

2. **Configure your models and teams in the config file (`config/permissions.php`)**
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

3. **Install**
Now run install command to create the necessary tables and models:
```bash
php artisan permissions:install
```

This will create the necessary database migrations, Pivot models, and enum classes for your user models and teams.

## Next Steps

Explore the components:
- [Roles](./components/roles.md)
- [Permissions](./components/permissions.md)
- [Teams](./components/teams.md)
- [Middlewares](./components/middlewares.md)
- [Artisan Commands](./components/commands.md)
