<?php

namespace App\Events;

use App\Models\Task;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  Task   $task              The task being assigned
     * @param  string $assignedUserId    The user who was assigned
     * @param  string $assignedByUserId  The actor who performed the assignment
     * @param  string $role              The role granted (admin|member|tester|designer)
     */
    public function __construct(
        public readonly Task   $task,
        public readonly string $assignedUserId,
        public readonly string $assignedByUserId,
        public readonly string $role,
    ) {}
}
