<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class WorkspaceScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // If there's an authenticated user and they have a workspace_id, restrict queries to that workspace.
        // We only enforce this if auth()->check() to prevent command line tasks or raw system queries from breaking, 
        // but if strictly required, we could just say: 
        if (Auth::hasUser() && Auth::user()->workspace_id) {
            $builder->where($model->getTable() . '.workspace_id', Auth::user()->workspace_id);
        }
    }
}
