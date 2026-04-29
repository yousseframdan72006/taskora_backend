<?php

namespace App\Events;

use App\Models\Task;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $task;
    public $action;
    public $oldValue;
    public $newValue;
    public $userId;

    /**
     * Create a new event instance.
     */
    public function __construct(Task $task, string $action, ?string $oldValue = null, ?string $newValue = null, ?string $userId = null)
    {
        $this->task = $task;
        $this->action = $action;
        $this->oldValue = $oldValue;
        $this->newValue = $newValue;
        $this->userId = $userId;
    }
}
