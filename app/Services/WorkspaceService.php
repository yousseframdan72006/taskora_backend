<?php

namespace App\Services;

use App\Models\Workspace;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Exception;

class WorkspaceService
{
    /**
     * Create a new workspace and make the user an admin.
     */
    public function create(array $data, User $user)
    {
        if (! $user->isIndependent()) {
            throw new Exception("You are already part of a workspace.");
        }

        return DB::transaction(function () use ($data, $user) {
            $workspace = Workspace::create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? null,
            ]);

            // Update user to be the admin of the newly created workspace
            $user->update([
                'workspace_id' => $workspace->id,
                'role' => 'owner',
            ]);

            return $workspace;
        });
    }
}
