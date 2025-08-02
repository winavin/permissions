<?php

namespace Winavin\Permissions\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Winavin\Permissions\Contracts\TeamInterface;

abstract class Permission extends MorphPivot
{
    public function scopeForTeam(Builder $query, ?TeamInterface $team): Builder
    {
        if (is_null($team)) {
            return $query->whereNull('team_type')->whereNull('team_id');
        }
        return $query->where('team_type', get_class($team))->where('team_id', $team?->id);
    }

    public function team(): MorphTo
    {
        return $this->morphTo('team');
    }
}