<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * List all in-app notifications for the authenticated user.
     * Ordered by newest first, paginated.
     */
    public function index(Request $request)
    {
        $notifications = Notification::where('notifiable_type', User::class)
            ->where('notifiable_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $notifications,
            'message' => 'Notifications retrieved successfully.',
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, Notification $notification)
    {
        // Authorization: only the owner can mark as read
        if ($notification->notifiable_id !== $request->user()->id) {
            abort(403, 'Unauthorized.');
        }

        $notification->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'data'    => $notification,
            'message' => 'Notification marked as read.',
        ]);
    }

    /**
     * Mark all notifications as read for the authenticated user.
     */
    public function markAllAsRead(Request $request)
    {
        Notification::where('notifiable_type', User::class)
            ->where('notifiable_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'data'    => [],
            'message' => 'All notifications marked as read.',
        ]);
    }
}
