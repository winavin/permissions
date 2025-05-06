<?php

namespace Winavin\Permissions\Console;

class PermissionsMakeModel extends BasePermissionsCommand
{
    protected $signature = 'permissions:make-model {name} {--path=}';
    protected $description = 'Create roles and permissions model/migration for user side';

    protected function process(): void
    {
        $this->publishMigrations();
        $this->publishModels();

        $this->info("✅ Models and migrations created for prefix: {$this->prefix()}");
    }

    protected function publishMigrations(): void
    {
        $stubPath = __DIR__ . '/../../stubs/migrations/migrations.php.stub';
        $timestamp = date('Y_m_d_His');
        $targetPath = database_path("migrations/{$timestamp}_create_{$this->prefixSnake()}_roles_permissions_tables.php");

        $this->publishStub($stubPath, $targetPath);
    }

    protected function publishModels(): void
    {
        $baseDir = app_path('Models');
        $subPath = trim($this->option('path') ?? '', '/');
        $modelDir = $subPath ? $baseDir . '/' . $subPath : $baseDir;
    
        $roleStub = __DIR__ . '/../../stubs/models/roles.php.stub';
        $roleTarget = "{$modelDir}/{$this->prefix()}Roles.php";
    
        $permissionStub = __DIR__ . '/../../stubs/models/permissions.php.stub';
        $permissionTarget = "{$modelDir}/{$this->prefix()}Permissions.php";
    
        $this->publishStub($roleStub, $roleTarget);
        $this->publishStub($permissionStub, $permissionTarget);
    }
}
