<?php

namespace App\Listeners;

use App\Events\TaskStatusChanged;
use App\Jobs\SendPushNotification;

class SendTaskStatusNotification
{
    /**
     * Handle the TaskStatusChanged event.
     *
     * Routing rules:
     *   review → testers only
     *   done   → admin + all participants
     *   other  → all participants
     *
     * The actor (changedByUserId) is always excluded via SendPushNotification.
     */
    public function handle(TaskStatusChanged $event): void
    {
        $task  = $event->task->load('users');
        $users = $task->users;

        [$title, $body, $targetIds] = match ($event->newStatus) {
            'review' => [
                'مراجعة مطلوبة',
                "المهمة جاهزة للمراجعة: {$task->title}",
                $users->where('pivot.role', 'tester')->pluck('id')->toArray(),
            ],
            'done' => [
                'مهمة مكتملة',
                "تم إنهاء المهمة: {$task->title}",
                $users->pluck('id')->toArray(),
            ],
            default => [
                'تحديث المهمة',
                "تم تحديث حالة المهمة إلى {$this->translateStatus($event->newStatus)}: {$task->title}",
                $users->pluck('id')->toArray(),
            ],
        };

        // Always include the workspace admin in status notifications
        $admin = \App\Models\User::where('workspace_id', $task->workspace_id)
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
            title:         $title,
            body:          $body,
            data:          [
                'type'       => 'task_status_changed',
                'task_id'    => $task->id,
                'new_status' => $event->newStatus,
                'old_status' => $event->oldStatus,
            ],
            excludeUserId: $event->changedByUserId,
        );
    }

    private function translateStatus(string $status): string
    {
        return match ($status) {
            'pending'     => 'معلّق',
            'in_progress' => 'قيد التنفيذ',
            'review'      => 'تحت المراجعة',
            'done'        => 'مكتمل',
            default       => $status,
        };
    }
}
