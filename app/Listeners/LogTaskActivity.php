<?php

namespace App\Listeners;

use App\Events\CommentAdded;
use App\Events\InviteSent;
use App\Events\TaskAssigned;
use App\Events\TaskChanged;
use App\Events\TaskStatusChanged;
use App\Services\TaskActivityService;

class LogTaskActivity
{
    protected TaskActivityService $activityService;

    /**
     * Create the event listener.
     */
    public function __construct(TaskActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    /**
     * Handle TaskChanged (general updates: title, description, priority, due_date).
     */
    public function handleTaskChanged(TaskChanged $event): void
    {
        $this->activityService->log(
            $event->task,
            $event->action,
            $event->oldValue,
            $event->newValue,
            $event->userId
        );
    }

    /**
     * Handle TaskAssigned — logs the assignment action.
     */
    public function handleTaskAssigned(TaskAssigned $event): void
    {
        $this->activityService->log(
            $event->task,
            'assigned',
            null,
            $event->role,
            $event->assignedByUserId
        );
    }

    /**
     * Handle TaskStatusChanged — logs the status transition.
     */
    public function handleTaskStatusChanged(TaskStatusChanged $event): void
    {
        $this->activityService->log(
            $event->task,
            'status_changed',
            $event->oldStatus,
            $event->newStatus,
            $event->changedByUserId
        );
    }
}
