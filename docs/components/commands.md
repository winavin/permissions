# Artisan Commands

The package comes with several Artisan commands to help you quickly set up your project.

## `permissions:install`

Installs the package by reading the `config/permissions.php` file, then generates the required Enum classes, Pivot Models, and Migrations for all configured user models and teams.

```bash
php artisan permissions:install
```

You can use the `--force` flag to overwrite existing files.

## `permissions:make-model`

Generates database migrations and model scaffolding for a specific user model.

```bash
php artisan permissions:make-model {Name} --path={Team}
```

Replace `{Name}` with your model prefix (e.g., `User`, `Customer`, `Employee`).
This publishes:
- A migration file for the roles and permissions tables.
- A model for the Roles entry.
- A model for the Permissions entry.

## `permissions:make-enums`

Creates role and permission Enums.

```bash
php artisan permissions:make-enums {Name} --path={Path}
```

This generates:
- `{Path}/{Name}Role.php` (Enum for Roles)
- `{Path}/{Name}Permission.php` (Enum for Permissions)

## `permissions:uninstall`

Removes generated Enums, Models, and Migrations for a specific model or team.

```bash
php artisan permissions:uninstall --model=User
```
