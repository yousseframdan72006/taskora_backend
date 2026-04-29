<?php

namespace App\Listeners;

use App\Events\CommentAdded;
use App\Jobs\SendPushNotification;

class SendCommentNotification
{
    /**
     * Handle the CommentAdded event.
     * Notifies all task participants except the commenter.
     */
    public function handle(CommentAdded $event): void
    {
        $task    = $event->task->load('users');
        $userIds = $task->users->pluck('id')->toArray();

        // Always include the workspace admin in comment notifications
        $admin = \App\Models\User::where('workspace_id', $task->workspace_id)
            ->where('role', 'admin')
            ->first();
            
        if ($admin && !in_array($admin->id, $userIds)) {
            $userIds[] = $admin->id;
        }

        if (empty($userIds)) {
            return;
        }

        SendPushNotification::dispatch(
            userIds:       $userIds,
            title:         'تعليق جديد',
            body:          "تم إضافة تعليق جديد على المهمة: {$task->title}",
            data:          [
                'type'       => 'comment_added',
                'task_id'    => $task->id,
                'comment_id' => $event->comment->id,
            ],
            excludeUserId: $event->actorId,
        );
    }
}
