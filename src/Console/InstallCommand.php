<?php

namespace Winavin\Permissions\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class InstallCommand extends Command
{
    protected $signature = 'permissions:install {--force}';
    protected $description = 'Generate necessary scaffolding as per configuration';

    public function handle(): void
    {
        $modelsConfig = config('permissions.models', []);
        $users = array_keys($modelsConfig);

        $this->generate('users', 'permissions:make-model', $users);

        if (config('permissions.teams.is_enabled', false)) {
            $teams = [];
            foreach ($modelsConfig as $userTeams) {
                if (is_array($userTeams)) {
                    $teams = array_merge($teams, $userTeams);
                }
            }
            $teams = array_unique($teams);
            $this->generate('teams', 'permissions:make-enums', $teams);
        } else {
            $this->info("Teams are not enabled in your configuration. Skipping team model generation.");
        }

        $this->info("✅ Permissions scaffolding installed successfully!");
    }

    protected function generate(string $type, string $command, array $models = []): void
    {
        foreach ($models as $model) {
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
