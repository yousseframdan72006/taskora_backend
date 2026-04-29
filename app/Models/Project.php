<?php

namespace App\Models;

use App\Models\Traits\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, HasUuids, SoftDeletes, BelongsToWorkspace;

    protected $guarded = [];
    protected $appends = ['progress'];

    public function getProgressAttribute()
    {
        $tasks = $this->tasks()->get();
        if ($tasks->isEmpty()) return 0;
        
        $weights = ['low' => 1, 'medium' => 2, 'high' => 3];
        $totalWeight = 0;
        $doneWeight = 0;

        foreach ($tasks as $task) {
            $weight = $weights[$task->priority ?? 'medium'] ?? 2;
            $totalWeight += $weight;
            if ($task->status === 'done') {
                $doneWeight += $weight;
            }
        }

        return $totalWeight === 0 ? 0 : (int) round(($doneWeight / $totalWeight) * 100);
    }

    public function updateStatus()
    {
        $tasks = $this->tasks()->get();
        if ($tasks->isEmpty()) {
            if ($this->status !== 'active') {
                $this->update(['status' => 'active']);
            }
            return;
        }

        $allDone = $tasks->every(fn($task) => $task->status === 'done');

        if ($allDone) {
            if ($this->status !== 'completed') {
                $this->update(['status' => 'completed']);
            }
        } else {
            if ($this->status !== 'active') {
                $this->update(['status' => 'active']);
            }
        }
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }
}
