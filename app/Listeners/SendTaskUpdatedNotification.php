<?php

namespace App\Listeners;

use App\Events\TaskUpdated;
use App\Jobs\SendPushNotification;

class SendTaskUpdatedNotification
{
    /**
     * Handle the TaskUpdated event.
     * Notifies all task participants (except the editor) that the task was updated.
     */
    public function handle(TaskUpdated $event): void
    {
        $task = $event->task->load('users');
        $userIds = $task->users->pluck('id')->toArray();

        if (empty($userIds)) {
            return;
        }

        $fieldsLabel = empty($event->changedFields)
            ? ''
            : ' (' . implode(', ', $this->translateFields($event->changedFields)) . ')';

        SendPushNotification::dispatch(
            userIds: $userIds,
            title: 'تحديث مهمة',
            body: "تم تعديل المهمة: {$task->title}{$fieldsLabel}",
            data: [
                'type' => 'task_updated',
                'task_id' => $task->id,
            ],
            excludeUserId: $event->updatedByUserId,
        );
    }

    private function translateFields(array $fields): array
    {
        $map = [
            'title' => 'العنوان',
            'description' => 'الوصف',
            'priority' => 'الأولوية',
            'due_date' => 'تاريخ الاستحقاق',
            'report_text' => 'تقرير العمل',
        ];

        return array_map(fn($f) => $map[$f] ?? $f, $fields);
    }
}
