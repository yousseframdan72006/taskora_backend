<?php

namespace App\Events;

use App\Models\Invite;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InviteSent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  Invite $invite  The newly created invite record
     */
    public function __construct(
        public readonly Invite $invite,
    ) {}
}
