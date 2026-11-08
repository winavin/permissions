<?php

namespace Winavin\Permissions\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as AuthUser;
use Orchestra\Testbench\TestCase as Orchestra;
use Winavin\Permissions\Contracts\TeamInterface;
use Winavin\Permissions\Models\Permission;
use Winavin\Permissions\Models\Role;
use Winavin\Permissions\PermissionsServiceProvider;
use Winavin\Permissions\Tests\Enums\TestPermission;
use Winavin\Permissions\Tests\Enums\TestRole;
use Winavin\Permissions\Traits\HasRolesAndPermissions;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/../stubs/migrations');
    }

    protected function getPackageProviders($app)
    {
        return [
            PermissionsServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('auth.providers.users.model', User::class);
        config()->set('app.key', 'base64:6Cu/ozj4w03U46sNItistlrIqM5525d97+z8Q8577qE=');

        /*
        $migration = include __DIR__.'/../database/migrations/create_permissions_table.php.stub';
        $migration->up();
        */

        $app['config']->set('permissions.models', [User::class => []]);
    }

    protected function defineDatabaseMigrations()
    {
        // $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        $this->app['db']->connection()->getSchemaBuilder()->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->timestamps();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->nullableMorphs('team');
            $table->string('role');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->nullableMorphs('team');
            $table->string('permission');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }
}

class User extends AuthUser
{
    use HasRolesAndPermissions;

    protected $guarded = [];
}

class UserRoles extends Role
{
    protected $table = 'user_roles';

    protected $guarded = [];

    protected $casts = [
        'role' => TestRole::class,
    ];
}

class UserPermissions extends Permission
{
    protected $table = 'user_permissions';

    protected $guarded = [];

    protected $casts = [
        'permission' => TestPermission::class,
    ];
}

class Team extends Model implements TeamInterface
{
    protected $table = 'teams';

    protected $guarded = [];
}
