<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectPolicy
{
    use HandlesAuthorization;
    
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'owner']);
    }

    public function update(User $user, Project $project): bool
    {
        return in_array($user->role, ['admin', 'owner']) && $user->workspace_id === $project->workspace_id;
    }

    public function delete(User $user, Project $project): bool
    {
        return in_array($user->role, ['admin', 'owner']) && $user->workspace_id === $project->workspace_id;
    }
}
