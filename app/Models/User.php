<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids, SoftDeletes;

    protected $guarded = [];

    protected $appends = ['avatar_url'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function invites()
    {
        return $this->hasMany(Invite::class);
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class)->withPivot('role')->withTimestamps();
    }

    public function devices()
    {
        return $this->hasMany(UserDevice::class);
    }

    public function isIndependent(): bool
    {
        return is_null($this->workspace_id);
    }

    public function isWorkspaceAdmin(): bool
    {
        return $this->workspace_id !== null && in_array($this->role, ['admin', 'owner']);
    }

    public function isWorkspaceOwner(): bool
    {
        return $this->workspace_id !== null && $this->role === 'owner';
    }

    public function isWorkspaceMember(): bool
    {
        return $this->workspace_id !== null && $this->role === 'member';
    }

    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        return null;
    }
}
