<?php

namespace App\Listeners;

use App\Events\InviteSent;
use App\Jobs\SendPushNotification;

class SendInviteNotification
{
    /**
     * Handle the InviteSent event.
     * Only sends a notification if the invited user already has an account.
     */
    public function handle(InviteSent $event): void
    {
        // If the invited email is not linked to an existing user, skip push.
        // (They'll get an email instead — handled separately.)
        if (empty($event->invite->user_id)) {
            return;
        }

        SendPushNotification::dispatch(
            userIds: [$event->invite->user_id],
            title:   'دعوة جديدة',
            body:    "تم دعوتك للانضمام إلى مساحة عمل كـ {$this->translateRole($event->invite->role)}",
            data:    [
                'type'      => 'invite_received',
                'invite_id' => $event->invite->id,
            ],
        );
    }

    private function translateRole(string $role): string
    {
        return match ($role) {
            'admin' => 'مدير',
            'member' => 'عضو',
            'reviewer' => 'مراجع',
            default => $role,
        };
    }
}
