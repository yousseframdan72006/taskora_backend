<?php

namespace App\Listeners;

use App\Events\ProjectDeleted;
use App\Jobs\SendPushNotification;

class SendProjectDeletedNotification
{
    /**
     * Handle the ProjectDeleted event.
     * Notifies all former project members that the project was deleted.
     */
    public function handle(ProjectDeleted $event): void
    {
        $targetIds = collect($event->memberIds)
            ->reject(fn($id) => $id === $event->deletedByUserId)
            ->values()
            ->toArray();

        if (empty($targetIds)) {
            return;
        }

        SendPushNotification::dispatch(
            userIds:       $targetIds,
            title:         'مشروع محذوف',
            body:          "تم حذف المشروع: {$event->projectName}",
            data:          [
                'type'         => 'project_deleted',
                'project_name' => $event->projectName,
            ],
            excludeUserId: $event->deletedByUserId,
        );
    }
}
