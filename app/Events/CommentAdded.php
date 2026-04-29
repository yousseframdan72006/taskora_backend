<?php

namespace App\Events;

use App\Models\Comment;
use App\Models\Task;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentAdded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  Task    $task     The task that received the comment
     * @param  Comment $comment  The newly created comment
     * @param  string  $actorId  The user who wrote the comment
     */
    public function __construct(
        public readonly Task    $task,
        public readonly Comment $comment,
        public readonly string  $actorId,
    ) {}
}
