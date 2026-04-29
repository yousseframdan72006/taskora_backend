<?php

namespace App\Models;

use App\Models\Traits\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invite extends Model
{
    use HasFactory, HasUuids, SoftDeletes, BelongsToWorkspace;

    // We do use BelongsToWorkspace because invites are within a workspace.
    // When querying all pending invites from a user's perspective during join (without a workspace yet),
    // they check via withoutGlobalScope() or Auth logic elsewhere. 

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
