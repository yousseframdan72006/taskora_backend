<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskPolicy
{
    use HandlesAuthorization;
    
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'owner']);
    }

    public function update(User $user, Task $task): bool
    {
        // Admin/Owner can update anything. 
        // Member can only update if assigned (specifically for status changes, handled in controller/gate)
        return in_array($user->role, ['admin', 'owner']) && $user->workspace_id === $task->workspace_id;
    }

    public function updateStatus(User $user, Task $task): bool
    {
        // Check if user is assigned to this task
        return $task->users()->where('users.id', $user->id)->exists();
    }

    public function delete(User $user, Task $task): bool
    {
        return in_array($user->role, ['admin', 'owner']) && $user->workspace_id === $task->workspace_id;
    }
}
