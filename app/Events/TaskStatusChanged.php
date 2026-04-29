<?php

namespace App\Events;

use App\Models\Task;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  Task   $task             The task whose status changed
     * @param  string $oldStatus        Previous status value
     * @param  string $newStatus        New status value
     * @param  string $changedByUserId  The actor who changed the status
     */
    public function __construct(
        public readonly Task   $task,
        public readonly string $oldStatus,
        public readonly string $newStatus,
        public readonly string $changedByUserId,
    ) {}
}
