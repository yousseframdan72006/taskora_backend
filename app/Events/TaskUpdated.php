<?php

namespace App\Events;

use App\Models\Task;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  Task   $task          The updated task
     * @param  string $updatedByUserId The actor who made the update
     * @param  array  $changedFields  List of field names that changed (for the notification body)
     */
    public function __construct(
        public readonly Task   $task,
        public readonly string $updatedByUserId,
        public readonly array  $changedFields = [],
    ) {}
}
