<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  string $taskTitle       The deleted task's title (stored before deletion)
     * @param  string $projectId       The project the task belonged to
     * @param  string $workspaceId     The workspace the task belonged to
     * @param  array  $participantIds  User IDs who were on the task
     * @param  string $deletedByUserId The actor who deleted the task
     */
    public function __construct(
        public readonly string $taskTitle,
        public readonly string $projectId,
        public readonly string $workspaceId,
        public readonly array  $participantIds,
        public readonly string $deletedByUserId,
    ) {}
}
