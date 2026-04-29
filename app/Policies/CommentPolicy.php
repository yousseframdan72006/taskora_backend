<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommentPolicy
{
    use HandlesAuthorization;

    public function delete(User $user, Comment $comment): bool
    {
        // Only the author or an admin can delete a comment
        return ($user->id === $comment->user_id || $user->role === 'admin') 
            && $user->workspace_id === $comment->workspace_id;
    }
}
