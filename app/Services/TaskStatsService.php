<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class TaskStatsService
{
    /**
     * Get aggregate statistics for multiple users.
     */
    public function getEmployeesStats(string $workspaceId)
    {
        return Cache::remember("workspace:{$workspaceId}:task_stats", 300, function () use ($workspaceId) {
            $users = User::where('workspace_id', $workspaceId)
                ->withCount($this->getAggregations())
                ->get();

            return $users->map(fn($u) => $this->formatStats($u))->toArray();
        });
    }

    /**
     * Get statistics for a single user.
     */
    public function getUserStats(User $user)
    {
        return Cache::remember("user:{$user->id}:task_stats", 300, function () use ($user) {
            $userWithStats = User::where('id', $user->id)
                ->withCount($this->getAggregations())
                ->first();

            return $this->formatStats($userWithStats);
        });
    }

    private function getAggregations()
    {
        return [
            'tasks as total_tasks',
            'tasks as todo_tasks' => fn($q) => $q->where('status', 'todo'),
            'tasks as in_progress_tasks' => fn($q) => $q->where('status', 'in_progress'),
            'tasks as review_tasks' => fn($q) => $q->where('status', 'review'),
            'tasks as done_tasks' => fn($q) => $q->where('status', 'done'),
            'tasks as overdue_tasks' => fn($q) => $q->where('status', '!=', 'done')->where('due_date', '<', now()),
        ];
    }

    private function formatStats($user)
    {
        $total = $user->total_tasks ?? 0;
        $done = $user->done_tasks ?? 0;
        
        $completionRate = $total > 0 ? round(($done / $total) * 100) : 0;

        return [
            'user_id' => (string) $user->id,
            'name' => $user->name,
            'stats' => [
                'total' => $total,
                'todo' => $user->todo_tasks ?? 0,
                'in_progress' => $user->in_progress_tasks ?? 0,
                'review' => $user->review_tasks ?? 0,
                'done' => $done,
                'completion_rate' => $completionRate,
                'overdue' => $user->overdue_tasks ?? 0,
            ]
        ];
    }
}
