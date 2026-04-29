<?php

namespace App\Events;

use App\Models\Workspace;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MemberRemovedFromWorkspace
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  Workspace $workspace      The workspace the user was removed from
     * @param  string    $removedUserId  The user who was removed
     * @param  string    $removedByUserId The admin who performed the removal
     */
    public function __construct(
        public readonly Workspace $workspace,
        public readonly string    $removedUserId,
        public readonly string    $removedByUserId,
    ) {}
}
