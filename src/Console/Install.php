<?php

namespace Winavin\Permissions\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class Install extends Command
{
    protected $signature = 'permissions:install {name}';
    protected $description = 'Install Roles and Permissions for given model';

    public function handle(): void
    {
        $this->publishMigrations();
        $this->publishEnums();
        $this->publishModels();

        $this->info("✅ Permissions setup installed for model: {$this->model()}");
    }

    protected function model(): string
    {
        return Str::studly($this->argument('name'));
    }

    protected function modelSnake(): string
    {
        return Str::snake($this->argument('name'));
    }

    public function publishMigrations(): void
    {
        $stubPath = __DIR__ . '/../../stubs/migrations/migrations.php.stub';
        $targetPath = database_path('migrations/' . date('Y_m_d_His') . "_create_{$this->modelSnake()}_roles_permissions_tables.php");

        $this->publishStub($stubPath, $targetPath);
    }

    public function publishEnums(): void
    {
        $enumDir = app_path('Enums');
        File::ensureDirectoryExists($enumDir);

        $roleStub = __DIR__ . '/../../stubs/enums/role.php.stub';
        $roleTarget = "{$enumDir}/{$this->model()}Role.php";

        $permissionStub = __DIR__ . '/../../stubs/enums/permission.php.stub';
        $permissionTarget = "{$enumDir}/{$this->model()}Permission.php";

        $this->publishStub($roleStub, $roleTarget);
        $this->publishStub($permissionStub, $permissionTarget);
    }

    public function publishModels(): void
    {
        $modelDir = app_path('Models');
        File::ensureDirectoryExists($modelDir);

        $roleStub = __DIR__ . '/../../stubs/models/roles.php.stub';
        $roleTarget = "{$modelDir}/{$this->model()}Roles.php";

        $permissionStub = __DIR__ . '/../../stubs/models/permissions.php.stub';
        $permissionTarget = "{$modelDir}/{$this->model()}Permissions.php";

        $this->publishStub($roleStub, $roleTarget);
        $this->publishStub($permissionStub, $permissionTarget);
    }

    protected function publishStub(string $stubPath, string $targetPath): void
    {
        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: $stubPath");
            return;
        }

        $content = File::get($stubPath);
        $content = str_replace(['{{Model}}', '{{model}}'], [$this->model(), $this->modelSnake()], $content);

        File::put($targetPath, $content);
        $this->info("✔ Published: " . $targetPath);
    }
}
