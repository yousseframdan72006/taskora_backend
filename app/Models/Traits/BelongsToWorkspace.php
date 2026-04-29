<?php

namespace App\Models\Traits;

use App\Models\Scopes\WorkspaceScope;
use Illuminate\Support\Facades\Auth;

trait BelongsToWorkspace
{
    /**
     * Boot the BelongsToWorkspace trait for a model.
     */
    protected static function bootBelongsToWorkspace(): void
    {
        static::addGlobalScope(new WorkspaceScope);

        static::creating(function ($model) {
            // Automatically set the workspace_id when creating a new model instance
            // If it hasn't been set explicitly, and we have an authenticated user with a workspace.
            if (! $model->workspace_id && Auth::hasUser() && Auth::user()->workspace_id) {
                $model->workspace_id = Auth::user()->workspace_id;
            }
        });
    }

    /**
     * Relationship to Workspace model.
     */
    public function workspace()
    {
        return $this->belongsTo(\App\Models\Workspace::class);
    }
}
