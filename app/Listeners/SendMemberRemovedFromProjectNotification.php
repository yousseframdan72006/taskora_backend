<?php

namespace App\Listeners;

use App\Events\MemberRemovedFromProject;
use App\Jobs\SendPushNotification;

class SendMemberRemovedFromProjectNotification
{
    /**
     * Handle the MemberRemovedFromProject event.
     * Notifies the removed user so they know they've been removed.
     */
    public function handle(MemberRemovedFromProject $event): void
    {
        SendPushNotification::dispatch(
            userIds:       [$event->removedUserId],
            title:         'تم إزالتك من مشروع',
            body:          "تم إزالة عضويتك من مشروع: {$event->projectName}",
            data:          [
                'type'       => 'removed_from_project',
                'project_id' => $event->project->id,
            ],
        );
    }
}
