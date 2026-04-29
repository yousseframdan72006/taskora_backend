<?php

namespace App\Listeners;

use App\Events\ProjectUpdated;
use App\Jobs\SendPushNotification;

class SendProjectUpdatedNotification
{
    /**
     * Handle the ProjectUpdated event.
     * Notifies all project members (except the editor) that the project was updated.
     */
    public function handle(ProjectUpdated $event): void
    {
        $project = $event->project->load('users');
        $userIds = $project->users->pluck('id')->toArray();

        if (empty($userIds)) {
            return;
        }

        SendPushNotification::dispatch(
            userIds:       $userIds,
            title:         'تحديث مشروع',
            body:          "تم تعديل المشروع: {$project->name}",
            data:          [
                'type'       => 'project_updated',
                'project_id' => $project->id,
            ],
            excludeUserId: $event->updatedByUserId,
        );
    }
}
