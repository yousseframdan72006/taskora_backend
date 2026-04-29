<?php

namespace App\Models\Traits;

use App\Models\Scopes\WorkspaceScope;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait HasWorkspace
{
    protected static function bootHasWorkspace()
    {
        static::addGlobalScope(new WorkspaceScope);

        static::creating(function ($model) {
            if (empty($model->workspace_id) && Auth::hasUser()) {
                $model->workspace_id = Auth::user()->workspace_id;
            }
        });
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
