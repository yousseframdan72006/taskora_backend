<?php

namespace App\Models;

use App\Models\Traits\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, HasUuids, SoftDeletes, BelongsToWorkspace;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function activities()
    {
        return $this->hasMany(TaskActivity::class);
    }
}
