<?php

namespace Winavin\Permissions\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class InstallCommand extends Command
{
    protected $signature   = 'permissions:install {--force}';
    protected $description = 'Generate necessary scaffolding as per configuration';

    public function handle(): void
    {
        $this->generate('users', 'permissions:make-model');

        if(config('permissions.is_teams_enabled', false)) {
            $this->generate('teams', 'permissions:make-enums');
        } else {
            $this->info("Teams are not enabled in your configuration. Skipping team model generation.");
        }

        $this->info("✅ Permissions scaffolding installed successfully!");
    }

    protected function generate(string $type, string $command): void
    {
        foreach (config("permissions.models.$type", []) as $model) {
            if (!class_exists($model)) {
                $this->error(ucfirst($type) . " model {$model} does not exist. Please check your configuration.");
                return;
            }

            $name = class_basename($model);

            $path = Str::of($model)
                       ->beforeLast($name)
                       ->after('Models')
                       ->trim('\\')
                       ->replaceLast('\\', '/')
                       ->toString();

            $this->call($command, [
                'name' => $name,
                '--path' => $path,
                '--force' => $this->option('force'),
            ]);
        }
    }
}
