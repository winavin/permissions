<?php

namespace Winavin\Permissions\Console;

class PermissionsMakeEnums extends BasePermissionsCommand
{
    protected $signature   = 'permissions:make-enums {name} {--path=}';
    protected $description = 'Create enums for team/organization-level roles and permissions';

    protected function process() : void
    {
        $this->publishEnums();
        $this->info( "✅ Enums created for prefix: {$this->prefix()}" );
    }

    protected function publishEnums() : void
    {
        $baseDir = app_path( 'Enums' );
        $subPath = trim( $this->option( 'path' ) ?? '', '/' );
        $enumDir = $subPath ? $baseDir . '/' . $subPath : $baseDir;

        $roleStub   = __DIR__ . '/../../stubs/enums/role.php.stub';
        $roleTarget = "{$enumDir}/{$this->prefix()}Role.php";

        $permissionStub   = __DIR__ . '/../../stubs/enums/permission.php.stub';
        $permissionTarget = "{$enumDir}/{$this->prefix()}Permission.php";

        $this->publishStub( $roleStub, $roleTarget );
        $this->publishStub( $permissionStub, $permissionTarget );
    }
}
