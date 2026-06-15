<?php

namespace Winavin\Permissions\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Winavin\Permissions\Contracts\TeamInterface;

abstract class Role extends MorphPivot
{
    #[Scope]
    public function forTeam(Builder $query, ?TeamInterface $team): Builder
    {
        if (is_null($team)) {
            return $query->whereNull('team_type')->whereNull('team_id');
        }
        $teamType = method_exists($team, 'getMorphClass') ? $team->getMorphClass() : get_class($team);
        return $query->where('team_type', $teamType)
                     ->where('team_id', $team->getKey());
    }

    public function team(): MorphTo
    {
        return $this->morphTo('team');
    }
}