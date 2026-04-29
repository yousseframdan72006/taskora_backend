<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProjectDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  string $projectName     The deleted project's name (captured before deletion)
     * @param  string $workspaceId     The workspace the project belonged to
     * @param  array  $memberIds       User IDs who were members of the project
     * @param  string $deletedByUserId The actor who deleted the project
     */
    public function __construct(
        public readonly string $projectName,
        public readonly string $workspaceId,
        public readonly array  $memberIds,
        public readonly string $deletedByUserId,
    ) {}
}
