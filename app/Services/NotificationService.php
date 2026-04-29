<?php

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    public function list()
    {
        return Notification::where('user_id', request()->user()->id)
            ->latest()
            ->paginate(15);
    }

    public function markAsRead(Notification $notification)
    {
        $notification->update(['is_read' => true]);
        return $notification;
    }
}
