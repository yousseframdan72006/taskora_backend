<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskActivity;

class TaskActivityService
{
    /**
     * Log an activity for a task.
     */
    public function log(Task $task, string $action, ?string $oldValue = null, ?string $newValue = null, ?string $userId = null)
    {
        // Try to get the user's role on the task if user is provided
        $role = null;
        if ($userId) {
            $userPivot = $task->users()->where('user_id', $userId)->first();
            if ($userPivot && $userPivot->pivot) {
                $role = $userPivot->pivot->role;
            } else {
                // If the user isn't directly attached but is an admin handling it
                $role = 'system_or_admin';
            }
        }

        TaskActivity::create([
            'task_id' => $task->id,
            'user_id' => $userId,
            'action' => $action,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'role' => $role,
        ]);
    }
}
