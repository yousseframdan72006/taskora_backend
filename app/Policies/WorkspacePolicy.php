<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Access\HandlesAuthorization;

class WorkspacePolicy
{
    use HandlesAuthorization;

    public function view(User $user, Workspace $workspace): bool
    {
        return $user->workspace_id === $workspace->id;
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return $user->workspace_id === $workspace->id && in_array($user->role, ['admin', 'owner']);
    }

    public function inviteUsers(User $user, Workspace $workspace): bool
    {
        return $user->workspace_id === $workspace->id && in_array($user->role, ['admin', 'owner']);
    }

    public function viewAllTaskStats(User $user, Workspace $workspace): bool
    {
        return $user->workspace_id === $workspace->id && in_array($user->role, ['admin', 'owner']);
    }
}
