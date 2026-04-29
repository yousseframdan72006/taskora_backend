<?php

namespace App\Listeners;

use App\Events\TaskAssigned;
use App\Jobs\SendPushNotification;

class SendTaskAssignedNotification
{
    /**
     * Handle the TaskAssigned event.
     * Sends a push notification to the newly assigned user only.
     */
    public function handle(TaskAssigned $event): void
    {
        SendPushNotification::dispatch(
            userIds:       [$event->assignedUserId],
            title:         'مهمة جديدة',
            body:          "تم إسناد مهمة جديدة لك: {$event->task->title}",
            data:          [
                'type'    => 'task_assigned',
                'task_id' => $event->task->id,
                'role'    => $event->role,
            ],
            excludeUserId: null, // Set to null to allow testing by assigning yourself
        );
    }
}
