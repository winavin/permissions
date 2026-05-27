# Middlewares

The package provides several middlewares to easily protect your routes based on roles or permissions.

## Available Middlewares

- `HasAnyRole`
- `HasAllRoles`
- `HasAnyPermission`
- `HasAllPermissions`

## Usage in Routes

You can use the middlewares directly in your routes or controllers, passing a pipe-separated (`|`) list of roles or permissions.

```php
use Winavin\Permissions\Middlewares\HasAnyRole;
use Winavin\Permissions\Middlewares\HasAllRoles;
use Winavin\Permissions\Middlewares\HasAnyPermission;
use Winavin\Permissions\Middlewares\HasAllPermissions;

// Allow access if the user has either the 'admin' OR 'manager' role
Route::get('/dashboard', function () {
    return 'Dashboard';
})->middleware(HasAnyRole::class . ':admin|manager');

// Allow access ONLY if the user has BOTH the 'admin' AND 'manager' roles
Route::get('/settings', function () {
    return 'Settings';
})->middleware(HasAllRoles::class . ':admin|manager');

// Allow access if the user has either 'create_post' OR 'edit_post' permission
Route::get('/posts/create', function () {
    return 'Create Post';
})->middleware(HasAnyPermission::class . ':create_post|edit_post');

// Allow access ONLY if the user has BOTH 'create_post' AND 'edit_post' permissions
Route::get('/posts/manage', function () {
    return 'Manage Posts';
})->middleware(HasAllPermissions::class . ':create_post|edit_post');
```

If the user fails the middleware check, an HTTP 403 Forbidden response will be thrown.
