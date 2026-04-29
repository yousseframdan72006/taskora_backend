<?php

namespace App\Listeners;

use App\Events\InviteResponded;
use App\Jobs\SendPushNotification;
use App\Models\User;

class SendInviteResponseNotification
{
    /**
     * Handle the InviteResponded event.
     * Notifies the workspace Admin about the user's accept/decline decision.
     */
    public function handle(InviteResponded $event): void
    {
        // Find the workspace admin to notify
        $admin = User::where('workspace_id', $event->invite->workspace_id)
            ->where('role', 'admin')
            ->first();

        if (!$admin) {
            return;
        }

        $isAccepted = $event->response === 'accepted';

        SendPushNotification::dispatch(
            userIds: [$admin->id],
            title:   $isAccepted ? 'قبول دعوة' : 'رفض دعوة',
            body:    $isAccepted
                ? "{$event->responderName} قبل الدعوة وانضم إلى مساحة العمل"
                : "{$event->responderName} رفض الدعوة للانضمام",
            data:    [
                'type'      => 'invite_response',
                'response'  => $event->response,
                'invite_id' => $event->invite->id,
                'user_id'   => $event->responderId,
            ],
        );
    }
}
