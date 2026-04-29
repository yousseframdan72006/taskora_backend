<?php

namespace App\Events;

use App\Models\Project;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProjectCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  Project $project       The newly created project
     * @param  string  $createdByUserId  The actor who created the project (excluded from notification)
     */
    public function __construct(
        public readonly Project $project,
        public readonly string  $createdByUserId,
    ) {}
}
