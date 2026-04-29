<?php

namespace App\Listeners;

use App\Events\ProjectCreated;
use App\Jobs\SendPushNotification;

class SendProjectCreatedNotification
{
    /**
     * Handle the ProjectCreated event.
     *
     * Recipients: all users assigned to the project EXCEPT the creator.
     *
     * Note: at the moment of creation, only the creator is attached (as admin).
     * So this notification is most useful AFTER members are assigned via
     * POST /api/projects/{project}/assign, but we fire it here intentionally
     * to notify any workspace members who were pre-assigned during creation.
     *
     * If your flow assigns members after creation, you may also dispatch
     * ProjectCreated from the assignMembers() method instead of / in addition to here.
     */
    public function handle(ProjectCreated $event): void
    {
        // Load all assigned users (pivot: project_user)
        $project = $event->project->load('users');
        $userIds = $project->users->pluck('id')->toArray();

        if (empty($userIds)) {
            return;
        }

        SendPushNotification::dispatch(
            userIds:       $userIds,
            title:         'مشروع جديد',
            body:          "تم إضافة مشروع جديد داخل مساحة العمل: {$project->name}",
            data:          [
                'type'         => 'project_created',
                'project_id'   => $project->id,
                'project_name' => $project->name,
                'workspace_id' => $project->workspace_id,
            ],
            excludeUserId: $event->createdByUserId,
        );
    }
}
