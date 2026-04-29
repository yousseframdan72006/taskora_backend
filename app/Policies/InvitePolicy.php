<?php

namespace App\Policies;

use App\Models\Invite;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvitePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can accept/decline the invite.
     */
    public function respond(User $user, Invite $invite): bool
    {
        return $user->id === $invite->user_id && $user->email === $invite->email;
    }
}
