<?php
  
namespace Winavin\Permissions\Traits;

trait TeamsHasRolesAndPermissions
{
    public function getRoleEnum(): string
    {
        return $this->resolveEnumClass('Role');
    }

    public function getPermissionEnum(): string
    {
        return $this->resolveEnumClass('Permission');
    }

    protected function resolveEnumClass(string $suffix): string
    {
        $modelClass = static::class;

        if (!str_starts_with($modelClass, 'App\\Models\\')) {
            throw new \RuntimeException("Model [$modelClass] is not within the expected [App\\Models\\] namespace.");
        }

        $relative = substr($modelClass, strlen('App\\Models\\'));
        $enumClass = 'App\\Enums\\' . $relative . $suffix;

        if (!class_exists($enumClass)) {
            throw new \RuntimeException("Enum class [$enumClass] not found.");
        }

        return $enumClass;
    }
}