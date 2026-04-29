<?php

namespace App\Listeners;

use App\Events\TaskDeleted;
use App\Jobs\SendPushNotification;

class SendTaskDeletedNotification
{
    /**
     * Handle the TaskDeleted event.
     * Notifies all task participants that the task was removed.
     */
    public function handle(TaskDeleted $event): void
    {
        $targetIds = collect($event->participantIds)
            ->reject(fn($id) => $id === $event->deletedByUserId)
            ->values()
            ->toArray();

        // Always include the workspace admin in deletion notifications
        $admin = \App\Models\User::where('workspace_id', $event->workspaceId)
            ->where('role', 'admin')
            ->first();
            
        if ($admin && !in_array($admin->id, $targetIds)) {
            $targetIds[] = $admin->id;
        }

        if (empty($targetIds)) {
            return;
        }

        SendPushNotification::dispatch(
            userIds:       $targetIds,
            title:         'مهمة محذوفة',
            body:          "تم حذف المهمة: {$event->taskTitle}",
            data:          [
                'type'       => 'task_deleted',
                'project_id' => $event->projectId,
            ],
            excludeUserId: $event->deletedByUserId,
        );
    }
}
