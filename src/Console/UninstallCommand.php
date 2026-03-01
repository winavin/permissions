<?php

namespace Winavin\Permissions\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class UninstallCommand extends Command
{
    protected $signature = 'permissions:uninstall {--model=} {--team=}';
    protected $description = 'Remove generated files (models, enums, migrations) based on configuration';

    public function handle(): void
    {
        $modelOpt = $this->option('model');
        $teamOpt = $this->option('team');
        $modelsConfig = config('permissions.models', []);

        $modelsToUninstall = [];
        $teamsToUninstall = [];

        if ($modelOpt && $teamOpt) {
            $modelsToUninstall[] = $modelOpt;
            $teamsToUninstall[] = $teamOpt;
        } elseif ($modelOpt && !$teamOpt) {
            $modelsToUninstall[] = $modelOpt;
            $modelTeams = $modelsConfig[$modelOpt] ?? [];
            if (is_array($modelTeams)) {
                $teamsToUninstall = array_merge($teamsToUninstall, $modelTeams);
            } elseif (is_string($modelTeams)) {
                $teamsToUninstall[] = $modelTeams;
            }
        } elseif (!$modelOpt && $teamOpt) {
            $teamsToUninstall[] = $teamOpt;
        } else {
            // Nothing given, uninstall all from config
            $modelsToUninstall = array_keys($modelsConfig);
            foreach ($modelsConfig as $userTeams) {
                if (is_array($userTeams)) {
                    $teamsToUninstall = array_merge($teamsToUninstall, $userTeams);
                } elseif (is_string($userTeams)) {
                    $teamsToUninstall[] = $userTeams;
                }
            }
        }

        $teamsToUninstall = array_unique($teamsToUninstall);

        foreach ($modelsToUninstall as $model) {
            if ($model) {
                $this->deleteModelFiles((string) $model);
            }
        }

        if (config('permissions.teams.is_enabled', false)) {
            foreach ($teamsToUninstall as $team) {
                if ($team) {
                    $this->deleteEnumFiles((string) $team);
                }
            }
        }

        $this->info("✅ Permissions scaffolding uninstalled successfully!");
    }

    protected function deleteModelFiles(string $model): void
    {
        $name = class_basename($model);
        $prefix = Str::studly($name);
        $prefixSnake = Str::snake($prefix);

        $path = Str::of($model)
            ->beforeLast($name)
            ->after('Models')
            ->trim('\\')
            ->replaceLast('\\', '/')
            ->toString();

        $baseDir = app_path('Models');
        $subPath = trim($path, '/');
        $modelDir = $subPath ? $baseDir . '/' . $subPath : $baseDir;

        $roleTarget = "{$modelDir}/{$prefix}Roles.php";
        $permissionTarget = "{$modelDir}/{$prefix}Permissions.php";

        if (File::exists($roleTarget)) {
            File::delete($roleTarget);
            $this->info("✔ Deleted: $roleTarget");
        }

        if (File::exists($permissionTarget)) {
            File::delete($permissionTarget);
            $this->info("✔ Deleted: $permissionTarget");
        }

        // Delete Migrations
        $migrationsPath = database_path('migrations');
        if (File::isDirectory($migrationsPath)) {
            $files = File::files($migrationsPath);
            foreach ($files as $file) {
                $filename = $file->getFilename();
                if (str_ends_with($filename, "_create_{$prefixSnake}_roles_permissions_tables.php")) {
                    File::delete($file->getPathname());
                    $this->info("✔ Deleted migration: {$filename}");
                }
            }
        }
    }

    protected function deleteEnumFiles(string $team): void
    {
        $name = class_basename($team);
        $prefix = Str::studly($name);

        $path = Str::of($team)
            ->beforeLast($name)
            ->after('Models')
            ->trim('\\')
            ->replaceLast('\\', '/')
            ->toString();

        $baseDir = app_path('Enums');
        $subPath = trim($path, '/');
        $enumDir = $subPath ? $baseDir . '/' . $subPath : $baseDir;

        $roleTarget = "{$enumDir}/{$prefix}Role.php";
        $permissionTarget = "{$enumDir}/{$prefix}Permission.php";

        if (File::exists($roleTarget)) {
            File::delete($roleTarget);
            $this->info("✔ Deleted: $roleTarget");
        }

        if (File::exists($permissionTarget)) {
            File::delete($permissionTarget);
            $this->info("✔ Deleted: $permissionTarget");
        }
    }
}
