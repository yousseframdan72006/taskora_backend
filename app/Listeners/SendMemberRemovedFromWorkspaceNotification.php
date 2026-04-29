<?php

namespace App\Listeners;

use App\Events\MemberRemovedFromWorkspace;
use App\Jobs\SendPushNotification;

class SendMemberRemovedFromWorkspaceNotification
{
    /**
     * Handle the MemberRemovedFromWorkspace event.
     * Notifies the removed user that they've been kicked from the workspace.
     */
    public function handle(MemberRemovedFromWorkspace $event): void
    {
        SendPushNotification::dispatch(
            userIds:       [$event->removedUserId],
            title:         'تم إزالتك من مساحة العمل',
            body:          "تم إزالة عضويتك من مساحة العمل: {$event->workspace->name}",
            data:          [
                'type'         => 'removed_from_workspace',
                'workspace_id' => $event->workspace->id,
            ],
        );
    }
}
