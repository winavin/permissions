<?php

namespace Winavin\Permissions;

use Illuminate\Support\ServiceProvider;

class PermissionsServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot() : void
    {
        // Publishing is only necessary when using the CLI.
        if( $this->app->runningInConsole() ) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register() : void
    {
        $this->mergeConfigFrom( __DIR__ . '/../config/permissions.php', 'permissions' );
    }

    protected function bootForConsole() : void
    {
        // Publishing the migration file.
        $this->publishesMigrations( [
                                        __DIR__ . '/../database/migrations' => database_path( 'migrations' ),
                                    ], 'permissions.migrations' );

        // Publishing the Enum files.
        $this->publishes( [
                              __DIR__ . '/../stubs/Role.php.stub' => app_path( 'Enums/Role.php' ),
                                                                                                            __DIR__ . '/../stubs/Permission.php.stub' => app_path( 'Enums/Permission.php' ),
                          ], 'permissions.stubs' );

        // Publishing the configuration file.
        $this->publishes( [
                              __DIR__ . '/../config/permissions.php' => config_path( 'permissions.php' ),
                          ], 'permissions.config' );

        $this->commands( [
                             \Winavin\Permissions\Console\PermissionsMakeModel::class,
                             \Winavin\Permissions\Console\PermissionsMakeEnums::class,
                         ] );
    }
}
