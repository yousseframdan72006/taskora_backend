<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    protected TaskStatsService $statsService;

    public function __construct(TaskStatsService $statsService)
    {
        $this->statsService = $statsService;
    }

    /**
     * Get task statistics overview for the dashboard based on user permissions.
     */
    public function getTaskOverview(User $user)
    {
        $workspaceId = $user->workspace_id;

        return Cache::remember("workspace:{$workspaceId}:dashboard_overview:user_{$user->id}", 300, function () use ($user, $workspaceId) {
            // Admin Flow
            if (Gate::allows('viewAllTaskStats', $user->workspace)) {
                $employeeStats = $this->statsService->getEmployeesStats($workspaceId);
                
                $totalTasks = array_sum(array_column(array_column($employeeStats, 'stats'), 'total'));
                $pendingTasks = array_sum(array_column(array_column($employeeStats, 'stats'), 'pending'));
                $inProgressTasks = array_sum(array_column(array_column($employeeStats, 'stats'), 'in_progress'));
                $doneTasks = array_sum(array_column(array_column($employeeStats, 'stats'), 'done'));
                
                $mostLoaded = null;
                $maxTasks = -1;
                foreach ($employeeStats as $employee) {
                    if ($employee['stats']['total'] > $maxTasks) {
                        $maxTasks = $employee['stats']['total'];
                        $mostLoaded = $employee;
                    }
                }

                return [
                    'overview' => [
                        'total_tasks' => $totalTasks,
                        'pending_tasks' => $pendingTasks,
                        'in_progress_tasks' => $inProgressTasks,
                        'done_tasks' => $doneTasks,
                        'most_loaded_user' => $mostLoaded ? ['id' => $mostLoaded['user_id'], 'name' => $mostLoaded['name'], 'tasks_count' => $maxTasks] : null,
                    ],
                    'employees_distribution' => $employeeStats
                ];
            }

            // Employee Flow
            return $this->statsService->getUserStats($user);
        });
    }
}
