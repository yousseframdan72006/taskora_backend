<?php

namespace App\Events;

use App\Models\Project;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MemberRemovedFromProject
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  Project $project         The project the user was removed from
     * @param  string  $removedUserId   The user who was removed
     * @param  string  $removedByUserId The admin who performed the removal
     */
    public function __construct(
        public readonly Project $project,
        public readonly string  $removedUserId,
        public readonly string  $removedByUserId,
    ) {}
}
