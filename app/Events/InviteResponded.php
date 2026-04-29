<?php

namespace App\Events;

use App\Models\Invite;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InviteResponded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  Invite $invite      The invite that was responded to
     * @param  string $response    'accepted' or 'declined'
     * @param  string $responderId The user who responded
     * @param  string $responderName The name of the user who responded
     */
    public function __construct(
        public readonly Invite $invite,
        public readonly string $response,
        public readonly string $responderId,
        public readonly string $responderName,
    ) {}
}
