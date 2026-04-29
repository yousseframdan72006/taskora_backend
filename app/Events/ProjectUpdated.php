<?php

namespace App\Events;

use App\Models\Project;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProjectUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  Project $project         The updated project
     * @param  string  $updatedByUserId The actor who made the update
     */
    public function __construct(
        public readonly Project $project,
        public readonly string  $updatedByUserId,
    ) {}
}
