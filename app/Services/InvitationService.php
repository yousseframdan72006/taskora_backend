<?php

namespace App\Services;

use App\Models\Invite;
use App\Models\Scopes\WorkspaceScope;
use App\Models\User;
use Exception;
use Illuminate\Support\Carbon;

class InvitationService
{
    /**
     * Generate an invite.
     */
    public function sendInvite(array $data, string $workspaceId)
    {
        // See if user exists to attach instantly
        $existingUser = User::where('email', $data['email'])->first();

        $inviteData = [
            'workspace_id' => $workspaceId,
            'email' => $data['email'],
            'role' => $data['role'] ?? 'member',
            'status' => 'pending',
            'expires_at' => Carbon::now()->addDays(7),
        ];

        if ($existingUser) {
            $inviteData['user_id'] = $existingUser->id;
        }

        return Invite::create($inviteData);
    }

    /**
     * Accept an invite.
     */
    public function accept(string $inviteId, User $user)
    {
        // Finding invite without Global Scope because user might not be in the workspace yet
        $invite = Invite::withoutGlobalScope(WorkspaceScope::class)
            ->where('id', $inviteId)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->firstOrFail();

        if (Carbon::now()->isAfter($invite->expires_at)) {
            $invite->update(['status' => 'expired']);
            throw new Exception("This invitation has expired.");
        }

        if (! $user->isIndependent()) {
            throw new Exception("You are already a member of a workspace.");
        }

        // Accept
        $invite->update(['status' => 'accepted']);
        
        $user->update([
            'workspace_id' => $invite->workspace_id,
            'role' => $invite->role,
        ]);

        \App\Services\ActivityLogger::log($invite->workspace_id, "{$user->name} accepted the invite and joined the workspace as {$invite->role}.");

        return $invite;
    }

    /**
     * Decline an invite.
     */
    public function decline(string $inviteId, User $user)
    {
        $invite = Invite::withoutGlobalScope(WorkspaceScope::class)
            ->where('id', $inviteId)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $invite->update(['status' => 'declined']);

        return $invite;
    }
}
